<?php

namespace Tests\Feature;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\CashSessions\Pages\CreateCashSession;
use App\Filament\Resources\CashSessions\Pages\ListCashSessions;
use App\Filament\Resources\CashSessions\Pages\ViewCashSession;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\CashSession;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Policies\ServicePolicy;
use App\Services\BillingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $reception;

    private User $vet;

    private User $accountant;

    private Owner $owner;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->reception = User::factory()->create();
        $this->reception->assignRole('recepcion');

        $this->vet = User::factory()->create();
        $this->vet->assignRole('veterinario');

        $this->accountant = User::factory()->create();
        $this->accountant->assignRole('contador');

        $this->owner = Owner::factory()->create();
        $this->patient = Patient::factory()->create(['owner_id' => $this->owner->getKey()]);

        $this->actingAs($this->admin);
    }

    public function test_administrators_can_manage_the_service_catalog(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'code' => 'CONS-TEST',
                'name' => 'Consulta de prueba',
                'category' => 'consulta',
                'description' => 'Servicio de prueba',
                'price_cents' => 80,
                'tax_rate' => 13,
                'duration_minutes' => 30,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', [
            'code' => 'CONS-TEST',
            'name' => 'Consulta de prueba',
            'price_cents' => 8000,
            'tax_rate' => 13,
        ]);

        $this->get(ListServices::getUrl())->assertOk();

        $this->actingAs($this->reception)
            ->get(ListServices::getUrl())
            ->assertForbidden();
    }

    public function test_issuing_a_receipt_calculates_discount_tax_and_totals(): void
    {
        $service = Service::factory()->create([
            'name' => 'Consulta general',
            'price_cents' => 10000,
            'tax_rate' => 13,
        ]);

        $invoice = app(BillingService::class)->issueInvoice([
            'owner_id' => $this->owner->getKey(),
            'patient_id' => $this->patient->getKey(),
            'items' => [[
                'catalog_item' => 'service:'.$service->getKey(),
                'description' => 'Consulta general',
                'quantity' => 2,
                'unit_price_cents' => 10000,
                'discount_cents' => 1000,
                'tax_rate' => 13,
            ]],
        ], $this->admin->getKey());

        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame(20000, $invoice->subtotal_cents);
        $this->assertSame(1000, $invoice->discount_cents);
        $this->assertSame(2470, $invoice->tax_cents);
        $this->assertSame(21470, $invoice->total_cents);
        $this->assertSame(0, $invoice->paid_cents);
        $this->assertSame(21470, $invoice->balance_cents);
        $this->assertSame($service->getKey(), $invoice->items->first()->service_id);
        $this->assertStringStartsWith(
            config('clinic.receipt_prefix').'-'.now()->format('Y').'-',
            (string) $invoice->number,
        );
    }

    public function test_receipt_numbers_increment_without_duplicates(): void
    {
        $first = $this->issueInvoice();
        $second = $this->issueInvoice();

        $this->assertNotSame($first->number, $second->number);
        $this->assertStringEndsWith('000001', (string) $first->number);
        $this->assertStringEndsWith('000002', (string) $second->number);
    }

    public function test_the_patient_must_belong_to_the_selected_owner(): void
    {
        $otherOwner = Owner::factory()->create();

        $this->expectException(ValidationException::class);

        app(BillingService::class)->issueInvoice([
            'owner_id' => $otherOwner->getKey(),
            'patient_id' => $this->patient->getKey(),
            'items' => [[
                'description' => 'Consulta',
                'quantity' => 1,
                'unit_price_cents' => 10000,
                'tax_rate' => 13,
            ]],
        ], $this->admin->getKey());
    }

    public function test_a_receipt_requires_at_least_one_item_and_a_positive_total(): void
    {
        $this->expectException(ValidationException::class);

        app(BillingService::class)->issueInvoice([
            'owner_id' => $this->owner->getKey(),
            'items' => [],
        ], $this->admin->getKey());
    }

    public function test_partial_and_full_payments_update_the_receipt(): void
    {
        $invoice = $this->issueInvoice();

        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 5000,
            'method' => PaymentMethod::Transfer,
            'reference' => 'TRF-001',
        ], $this->admin->getKey());

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame(5000, $invoice->paid_cents);
        $this->assertSame($invoice->total_cents - 5000, $invoice->balance_cents);

        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => $invoice->balance_cents,
            'method' => PaymentMethod::Card,
        ], $this->admin->getKey());

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame($invoice->total_cents, $invoice->paid_cents);
        $this->assertSame(0, $invoice->balance_cents);
        $this->assertSame(2, $invoice->payments()->count());
    }

    public function test_payments_cannot_exceed_the_balance(): void
    {
        $invoice = $this->issueInvoice();

        try {
            app(BillingService::class)->recordPayment($invoice, [
                'amount_cents' => $invoice->balance_cents + 1,
                'method' => PaymentMethod::Transfer,
            ], $this->admin->getKey());

            $this->fail('A payment above the balance should have failed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount_cents', $exception->errors());
        }

        $this->assertSame(0, $invoice->payments()->count());
        $this->assertSame($invoice->total_cents, $invoice->refresh()->balance_cents);
    }

    public function test_cash_payments_require_an_open_cash_session_and_create_a_movement(): void
    {
        $invoice = $this->issueInvoice();

        try {
            app(BillingService::class)->recordPayment($invoice, [
                'amount_cents' => 5000,
                'method' => PaymentMethod::Cash,
            ], $this->admin->getKey());

            $this->fail('Cash payments without an open session should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('method', $exception->errors());
        }

        $session = app(BillingService::class)->openCashSession(
            userId: $this->admin->getKey(),
            openingCents: 10000,
        );

        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 5000,
            'method' => PaymentMethod::Cash,
        ], $this->admin->getKey());

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->getKey(),
            'amount_cents' => 5000,
            'method' => PaymentMethod::Cash->value,
            'cash_session_id' => $session->getKey(),
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $session->getKey(),
            'type' => 'payment',
            'amount_cents' => 5000,
        ]);
    }

    public function test_non_cash_payments_do_not_require_a_cash_session(): void
    {
        $invoice = $this->issueInvoice();

        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 5000,
            'method' => PaymentMethod::Qr,
        ], $this->admin->getKey());

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->getKey(),
            'method' => PaymentMethod::Qr->value,
            'cash_session_id' => null,
        ]);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_unpaid_receipts_can_be_voided_with_a_reason(): void
    {
        $invoice = $this->issueInvoice();

        app(BillingService::class)->voidInvoice($invoice, 'Error de cobro', $this->admin->getKey());

        $invoice->refresh();

        $this->assertSame(InvoiceStatus::Void, $invoice->status);
        $this->assertSame(0, $invoice->balance_cents);
        $this->assertSame('Error de cobro', $invoice->void_reason);
        $this->assertSame($this->admin->getKey(), $invoice->voided_by);
        $this->assertNotNull($invoice->voided_at);

        $this->expectException(ValidationException::class);
        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 1000,
            'method' => PaymentMethod::Transfer,
        ], $this->admin->getKey());
    }

    public function test_receipts_with_payments_cannot_be_voided(): void
    {
        $invoice = $this->issueInvoice();

        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 1000,
            'method' => PaymentMethod::Transfer,
        ], $this->admin->getKey());

        $this->expectException(ValidationException::class);
        app(BillingService::class)->voidInvoice($invoice, 'Intento de anulación', $this->admin->getKey());
    }

    public function test_cash_session_arqueo_calculates_expected_and_difference(): void
    {
        $session = app(BillingService::class)->openCashSession(
            userId: $this->admin->getKey(),
            openingCents: 10000,
        );

        $invoice = $this->issueInvoice();

        app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 5000,
            'method' => PaymentMethod::Cash,
        ], $this->admin->getKey());

        app(BillingService::class)->registerCashMovement(
            session: $session,
            type: CashMovementType::Deposit,
            amountCents: 2000,
            description: 'Ingreso extra',
            userId: $this->admin->getKey(),
        );

        app(BillingService::class)->registerCashMovement(
            session: $session,
            type: CashMovementType::Withdrawal,
            amountCents: 1000,
            description: 'Retiro para cambio',
            userId: $this->admin->getKey(),
        );

        $closed = app(BillingService::class)->closeCashSession(
            session: $session,
            countedCents: 15000,
            userId: $this->admin->getKey(),
            notes: 'Arqueo de prueba',
        );

        $this->assertSame(CashSessionStatus::Closed, $closed->status);
        $this->assertSame(16000, $closed->expected_cents);
        $this->assertSame(15000, $closed->counted_cents);
        $this->assertSame(-1000, $closed->difference_cents);

        $this->expectException(ValidationException::class);
        app(BillingService::class)->closeCashSession($closed, 15000, $this->admin->getKey());
    }

    public function test_only_one_cash_session_can_be_open(): void
    {
        app(BillingService::class)->openCashSession($this->admin->getKey(), 0);

        $this->expectException(ValidationException::class);
        app(BillingService::class)->openCashSession($this->admin->getKey(), 0);
    }

    public function test_closed_cash_sessions_reject_manual_movements(): void
    {
        $session = CashSession::factory()->closed()->create();

        $this->expectException(ValidationException::class);
        app(BillingService::class)->registerCashMovement(
            session: $session,
            type: CashMovementType::Deposit,
            amountCents: 1000,
            description: 'Intento',
            userId: $this->admin->getKey(),
        );
    }

    public function test_financial_records_are_immutable(): void
    {
        $invoice = $this->issueInvoice();
        $payment = app(BillingService::class)->recordPayment($invoice, [
            'amount_cents' => 1000,
            'method' => PaymentMethod::Transfer,
        ], $this->admin->getKey());

        $this->assertFalse(Gate::forUser($this->reception)->allows('update', $invoice));
        $this->assertFalse(Gate::forUser($this->reception)->allows('delete', $invoice));
        $this->assertFalse(Gate::forUser($this->reception)->allows('update', $payment));
        $this->assertFalse(Gate::forUser($this->reception)->allows('delete', $payment));
    }

    public function test_services_used_in_receipts_cannot_be_deleted(): void
    {
        $service = Service::factory()->create();
        $invoice = $this->issueInvoice();

        $invoice->items()->first()->update(['service_id' => $service->getKey()]);

        $unused = Service::factory()->create();
        $policy = new ServicePolicy;

        $this->assertFalse($policy->delete($this->admin, $service));
        $this->assertTrue($policy->delete($this->admin, $unused));
    }

    public function test_reception_can_issue_receipts_and_operate_the_cash(): void
    {
        $this->actingAs($this->reception)
            ->get(ListInvoices::getUrl())
            ->assertOk();

        $this->actingAs($this->reception)
            ->get(CreateInvoice::getUrl())
            ->assertOk();

        $this->actingAs($this->reception)
            ->get(ListCashSessions::getUrl())
            ->assertOk();

        $this->actingAs($this->reception)
            ->get(CreateCashSession::getUrl())
            ->assertOk();
    }

    public function test_veterinarians_and_accountants_have_restricted_billing_access(): void
    {
        $this->actingAs($this->vet)
            ->get(ListInvoices::getUrl())
            ->assertForbidden();

        $this->actingAs($this->vet)
            ->get(ListCashSessions::getUrl())
            ->assertForbidden();

        $this->actingAs($this->accountant)
            ->get(ListInvoices::getUrl())
            ->assertOk();

        $this->actingAs($this->accountant)
            ->get(CreateInvoice::getUrl())
            ->assertForbidden();
    }

    public function test_receipt_view_and_print_pages_render(): void
    {
        $invoice = $this->issueInvoice();

        Livewire::test(ViewInvoice::class, ['record' => $invoice->getKey()])
            ->assertOk();

        $this->get(route('invoices.receipt', $invoice))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee($this->owner->full_name);
    }

    public function test_voided_receipts_show_the_void_banner_on_the_print_page(): void
    {
        $invoice = $this->issueInvoice();

        app(BillingService::class)->voidInvoice($invoice, 'Cobro duplicado', $this->admin->getKey());

        $this->get(route('invoices.receipt', $invoice))
            ->assertOk()
            ->assertSee('Recibo anulado')
            ->assertSee('Cobro duplicado');
    }

    public function test_receipt_and_cash_session_can_be_created_from_the_panel(): void
    {
        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'owner_id' => $this->owner->getKey(),
                'patient_id' => $this->patient->getKey(),
                'issued_at' => today()->toDateString(),
                'items' => [[
                    'description' => 'Consulta general',
                    'quantity' => 2,
                    'unit_price_cents' => 50,
                    'discount_cents' => 5,
                    'tax_rate' => 13,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::query()->latest('id')->firstOrFail();

        $this->assertSame(10000, $invoice->subtotal_cents);
        $this->assertSame(500, $invoice->discount_cents);
        $this->assertSame(1235, $invoice->tax_cents);
        $this->assertSame(10735, $invoice->total_cents);

        Livewire::test(CreateCashSession::class)
            ->fillForm([
                'opening_cents' => 150,
                'notes' => 'Turno de prueba',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $session = CashSession::query()->firstOrFail();

        $this->assertSame(15000, $session->opening_cents);
        $this->assertSame(CashSessionStatus::Open, $session->status);

        Livewire::test(ViewCashSession::class, ['record' => $session->getKey()])
            ->callAction('close', [
                'counted_cents' => 150,
                'notes' => 'Cierre de prueba',
            ])
            ->assertNotified();

        $session->refresh();

        $this->assertSame(CashSessionStatus::Closed, $session->status);
        $this->assertSame(15000, $session->expected_cents);
        $this->assertSame(15000, $session->counted_cents);
        $this->assertSame(0, $session->difference_cents);
    }

    private function issueInvoice(): Invoice
    {
        return app(BillingService::class)->issueInvoice([
            'owner_id' => $this->owner->getKey(),
            'patient_id' => $this->patient->getKey(),
            'items' => [[
                'description' => 'Consulta general',
                'quantity' => 1,
                'unit_price_cents' => 10000,
                'discount_cents' => 0,
                'tax_rate' => 13,
            ]],
        ], $this->admin->getKey());
    }
}
