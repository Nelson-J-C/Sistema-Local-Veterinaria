<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Filament\Pages\Reports;
use App\Filament\Widgets\BillingOverviewWidget;
use App\Filament\Widgets\PendingPaymentsWidget;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Product;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $accountant;

    private User $vet;

    private Owner $owner;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->accountant = User::factory()->create();
        $this->accountant->assignRole('contador');

        $this->vet = User::factory()->create();
        $this->vet->assignRole('veterinario');

        $this->owner = Owner::factory()->create();
        $this->patient = Patient::factory()->create(['owner_id' => $this->owner->getKey()]);

        $this->actingAs($this->admin);
    }

    public function test_reports_page_renders_all_sections(): void
    {
        $this->issueInvoice();
        Appointment::factory()->create([
            'vet_id' => $this->vet->getKey(),
            'starts_at' => today()->setTime(9, 0),
            'ends_at' => today()->setTime(9, 30),
        ]);

        Livewire::test(Reports::class)
            ->assertOk()
            ->assertSee('Ventas por día')
            ->assertSee('Pagos por método')
            ->assertSee('Citas por estado')
            ->assertSee('Citas por veterinario')
            ->assertSee('Stock bajo')
            ->assertSee('Lotes por vencer');
    }

    public function test_sales_report_excludes_voided_receipts_and_totals_the_period(): void
    {
        $paid = $this->issueInvoice([
            'issued_at' => today()->subDay()->toDateString(),
            'items' => [[
                'description' => 'Consulta general',
                'quantity' => 1,
                'unit_price_cents' => 10000,
                'discount_cents' => 0,
                'tax_rate' => 13,
            ]],
        ]);

        app(BillingService::class)->recordPayment($paid, [
            'amount_cents' => 5000,
            'method' => PaymentMethod::Transfer,
        ], $this->admin->getKey());

        $voided = $this->issueInvoice([
            'issued_at' => today()->toDateString(),
            'items' => [[
                'description' => 'Consulta anulada',
                'quantity' => 1,
                'unit_price_cents' => 20000,
                'discount_cents' => 0,
                'tax_rate' => 13,
            ]],
        ]);

        app(BillingService::class)->voidInvoice($voided, 'Cobro duplicado', $this->admin->getKey());

        $service = app(ReportService::class);
        $rows = $service->salesByDay(today()->subDays(2), today());

        $this->assertCount(1, $rows);
        $this->assertSame($paid->issued_at->toDateString(), $rows->first()['date']);
        $this->assertSame(1, $rows->first()['receipts']);
        $this->assertSame(11300, $rows->first()['total_cents']);
        $this->assertSame(5000, $rows->first()['paid_cents']);
        $this->assertSame(6300, $rows->first()['balance_cents']);

        $totals = $service->salesTotals($rows);

        $this->assertSame(11300, $totals['total_cents']);
        $this->assertSame(6300, $totals['balance_cents']);
    }

    public function test_reports_require_the_reports_permission(): void
    {
        $withoutRole = User::factory()->create();

        $this->actingAs($withoutRole)
            ->get(Reports::getUrl())
            ->assertForbidden();

        $this->actingAs($this->accountant)
            ->get(Reports::getUrl())
            ->assertOk();

        $this->actingAs($this->vet)
            ->get(Reports::getUrl())
            ->assertOk();
    }

    public function test_sales_export_streams_a_csv_with_the_daily_totals(): void
    {
        $this->issueInvoice();

        $response = $this->actingAs($this->admin)->get(route('reports.sales.export', [
            'from' => today()->toDateString(),
            'until' => today()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Fecha,Recibos,Subtotal,Descuento,IVA,Total,Cobrado,Saldo', $content);
        $this->assertStringContainsString('113.00', $content);
    }

    public function test_export_routes_reject_users_without_reports_permission(): void
    {
        $withoutRole = User::factory()->create();

        $this->actingAs($withoutRole)
            ->get(route('reports.sales.export'))
            ->assertForbidden();

        $this->actingAs($withoutRole)
            ->get(route('reports.appointments.export'))
            ->assertForbidden();

        $this->actingAs($withoutRole)
            ->get(route('reports.inventory.export'))
            ->assertForbidden();
    }

    public function test_appointments_report_respects_the_veterinarian_filter(): void
    {
        $otherVet = User::factory()->create();
        $otherVet->assignRole('veterinario');

        Appointment::factory()->create([
            'vet_id' => $this->vet->getKey(),
            'starts_at' => today()->setTime(9, 0),
            'ends_at' => today()->setTime(9, 30),
        ]);

        Appointment::factory()->create([
            'vet_id' => $otherVet->getKey(),
            'starts_at' => today()->setTime(10, 0),
            'ends_at' => today()->setTime(10, 30),
        ]);

        $service = app(ReportService::class);

        $all = $service->appointmentsByStatus(today(), today());
        $this->assertSame(2, $all->first()['appointments']);

        $filtered = $service->appointmentsByStatus(today(), today(), $this->vet->getKey());
        $this->assertCount(1, $filtered);
        $this->assertSame(1, $filtered->first()['appointments']);

        $byVet = $service->appointmentsByVet(today(), today());
        $this->assertSame(2, $byVet->count());
    }

    public function test_pending_payments_widget_lists_only_receipts_with_balance(): void
    {
        $pending = $this->issueInvoice();
        $paid = $this->issueInvoice();

        app(BillingService::class)->recordPayment($paid, [
            'amount_cents' => $paid->total_cents,
            'method' => PaymentMethod::Transfer,
        ], $this->admin->getKey());

        Livewire::test(PendingPaymentsWidget::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$paid]);
    }

    public function test_billing_overview_widget_renders_the_cash_and_receivable_summary(): void
    {
        $this->issueInvoice();

        app(BillingService::class)->openCashSession($this->admin->getKey(), 10000);

        Livewire::test(BillingOverviewWidget::class)
            ->assertOk()
            ->assertSee('Cobrado hoy')
            ->assertSee('Por cobrar')
            ->assertSee('Caja')
            ->assertSee('Abierta');
    }

    public function test_inventory_export_lists_low_stock_products(): void
    {
        Product::factory()->create([
            'name' => 'Producto critico de prueba',
            'stock_quantity' => 1,
            'min_stock' => 5,
            'track_stock' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.inventory.export'));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Stock bajo', $content);
        $this->assertStringContainsString('Producto critico de prueba', $content);
    }

    /**
     * Issue a receipt for the shared owner and patient.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function issueInvoice(array $overrides = []): Invoice
    {
        return app(BillingService::class)->issueInvoice(array_merge([
            'owner_id' => $this->owner->getKey(),
            'patient_id' => $this->patient->getKey(),
            'items' => [[
                'description' => 'Consulta general',
                'quantity' => 1,
                'unit_price_cents' => 10000,
                'discount_cents' => 0,
                'tax_rate' => 13,
            ]],
        ], $overrides), $this->admin->getKey());
    }
}
