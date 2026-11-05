<?php

namespace App\Services;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(
        private readonly NumberGenerator $numbers,
    ) {}

    /**
     * Issue an internal receipt with its lines and computed totals.
     *
     * @param  array{
     *     owner_id: int,
     *     patient_id?: int|null,
     *     issued_at?: string|\DateTimeInterface|null,
     *     due_at?: string|null,
     *     notes?: string|null,
     *     document_type?: InvoiceDocumentType|string|null,
     *     items: array<int, array{
     *         catalog_item?: string|null,
     *         product_id?: int|null,
     *         service_id?: int|null,
     *         description: string,
     *         quantity: float|int|string,
     *         unit_price_cents: float|int|string,
     *         discount_cents?: float|int|string|null,
     *         tax_rate?: float|int|string|null
     *     }>
     * }  $data
     */
    public function issueInvoice(array $data, int $userId): Invoice
    {
        return DB::transaction(function () use ($data, $userId): Invoice {
            $owner = Owner::query()->findOrFail($data['owner_id']);
            $patientId = isset($data['patient_id']) ? (int) $data['patient_id'] : null;

            if ($patientId !== null) {
                $patient = Patient::query()->findOrFail($patientId);

                if ((int) $patient->owner_id !== (int) $owner->getKey()) {
                    throw ValidationException::withMessages([
                        'patient_id' => 'El paciente seleccionado no pertenece al tutor.',
                    ]);
                }
            }

            $items = $data['items'];

            if ($items === []) {
                throw ValidationException::withMessages([
                    'items' => 'El recibo debe tener al menos un concepto.',
                ]);
            }

            $issuedAt = Carbon::parse($data['issued_at'] ?? now());
            $documentType = $data['document_type'] ?? InvoiceDocumentType::Receipt;

            if (is_string($documentType)) {
                $documentType = InvoiceDocumentType::from($documentType);
            }

            $invoice = Invoice::query()->create([
                'document_type' => $documentType,
                'number' => $this->receiptNumber($issuedAt),
                'owner_id' => $owner->getKey(),
                'patient_id' => $patientId,
                'status' => InvoiceStatus::Issued,
                'issued_at' => $issuedAt,
                'due_at' => $data['due_at'] ?? null,
                'subtotal_cents' => 0,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 0,
                'paid_cents' => 0,
                'balance_cents' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $subtotal = 0;
            $discount = 0;
            $tax = 0;

            foreach (array_values($items) as $index => $item) {
                $quantity = round((float) $item['quantity'], 3);
                $unitPrice = $this->toCents($item['unit_price_cents']);
                $lineDiscount = $this->toCents($item['discount_cents'] ?? 0);
                $taxRate = round((float) ($item['tax_rate'] ?? config('clinic.tax_rate')), 2);
                $description = trim((string) $item['description']);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Cada concepto debe tener una cantidad mayor a cero.',
                    ]);
                }

                if ($description === '') {
                    throw ValidationException::withMessages([
                        'items' => 'Cada concepto debe tener una descripción.',
                    ]);
                }

                if ($taxRate < 0 || $taxRate > 100) {
                    throw ValidationException::withMessages([
                        'items' => 'El IVA debe estar entre 0 y 100 %.',
                    ]);
                }

                $lineSubtotal = (int) round($quantity * $unitPrice);

                if ($lineDiscount > $lineSubtotal) {
                    throw ValidationException::withMessages([
                        'items' => 'El descuento no puede superar el importe del concepto.',
                    ]);
                }

                $taxable = $lineSubtotal - $lineDiscount;
                $lineTax = (int) round($taxable * $taxRate / 100);
                $lineTotal = $taxable + $lineTax;

                [$productId, $serviceId] = $this->resolveCatalog(
                    catalogItem: $item['catalog_item'] ?? null,
                    productId: $item['product_id'] ?? null,
                    serviceId: $item['service_id'] ?? null,
                );

                $invoice->items()->create([
                    'product_id' => $productId,
                    'service_id' => $serviceId,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPrice,
                    'discount_cents' => $lineDiscount,
                    'tax_rate' => $taxRate,
                    'line_subtotal_cents' => $lineSubtotal,
                    'line_tax_cents' => $lineTax,
                    'line_total_cents' => $lineTotal,
                    'sort_order' => $index,
                ]);

                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $lineTax;
            }

            $total = $subtotal - $discount + $tax;

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'El total del recibo debe ser mayor a cero.',
                ]);
            }

            $invoice->update([
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'tax_cents' => $tax,
                'total_cents' => $total,
                'balance_cents' => $total,
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Register a payment against a receipt.
     *
     * Cash payments require an open cash session and generate a cash movement.
     *
     * @param  array{
     *     amount_cents: float|int|string,
     *     method: PaymentMethod|string,
     *     reference?: string|null,
     *     paid_at?: string|\DateTimeInterface|null,
     *     notes?: string|null
     * }  $data
     */
    public function recordPayment(Invoice $invoice, array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $userId): Payment {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            if (! $invoice->canReceivePayments()) {
                throw ValidationException::withMessages([
                    'amount_cents' => 'El recibo no admite pagos: está anulado, pagado o sin saldo.',
                ]);
            }

            $amount = $this->toCents($data['amount_cents']);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount_cents' => 'El pago debe ser mayor a cero.',
                ]);
            }

            $balance = (int) $invoice->balance_cents;

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount_cents' => sprintf(
                        'El pago no puede superar el saldo pendiente (%s).',
                        Format::money($balance),
                    ),
                ]);
            }

            $method = $data['method'];

            if (is_string($method)) {
                $method = PaymentMethod::from($method);
            }

            $cashSession = null;

            if ($method->requiresCashSession()) {
                $cashSession = CashSession::query()
                    ->where('status', CashSessionStatus::Open)
                    ->lockForUpdate()
                    ->first();

                if ($cashSession === null) {
                    throw ValidationException::withMessages([
                        'method' => 'Debe abrir una sesión de caja antes de cobrar en efectivo.',
                    ]);
                }
            }

            $payment = $invoice->payments()->create([
                'amount_cents' => $amount,
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'paid_at' => Carbon::parse($data['paid_at'] ?? now()),
                'received_by' => $userId,
                'cash_session_id' => $cashSession?->getKey(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($cashSession !== null) {
                $cashSession->movements()->create([
                    'type' => CashMovementType::Payment,
                    'amount_cents' => $amount,
                    'description' => 'Cobro del recibo '.$invoice->number,
                    'reference' => $invoice->number,
                    'payment_id' => $payment->getKey(),
                    'user_id' => $userId,
                    'occurred_at' => $payment->paid_at,
                ]);
            }

            $paid = (int) $invoice->paid_cents + $amount;
            $remaining = max(0, (int) $invoice->total_cents - $paid);

            $invoice->update([
                'paid_cents' => $paid,
                'balance_cents' => $remaining,
                'status' => $remaining === 0 ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid,
            ]);

            return $payment->refresh();
        });
    }

    /**
     * Void an unpaid receipt, keeping the reason and the audit trail.
     */
    public function voidInvoice(Invoice $invoice, string $reason, int $userId): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason, $userId): Invoice {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $reason = trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'void_reason' => 'Indique el motivo de la anulación.',
                ]);
            }

            if (! $invoice->canBeVoided()) {
                throw ValidationException::withMessages([
                    'void_reason' => 'No se puede anular un recibo pagado o ya anulado. Registre primero la devolución correspondiente.',
                ]);
            }

            $invoice->update([
                'status' => InvoiceStatus::Void,
                'balance_cents' => 0,
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Open a new cash session. Only one session can be open at a time.
     */
    public function openCashSession(int $userId, int $openingCents, ?string $notes = null): CashSession
    {
        return DB::transaction(function () use ($userId, $openingCents, $notes): CashSession {
            $open = CashSession::query()
                ->where('status', CashSessionStatus::Open)
                ->lockForUpdate()
                ->first();

            if ($open !== null) {
                throw ValidationException::withMessages([
                    'opening_cents' => 'Ya existe una caja abierta. Ciérrela antes de abrir otra.',
                ]);
            }

            return CashSession::query()->create([
                'opened_by' => $userId,
                'opened_at' => now(),
                'opening_cents' => max(0, $openingCents),
                'status' => CashSessionStatus::Open,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Close the cash session and register the arqueo.
     */
    public function closeCashSession(CashSession $session, int $countedCents, int $userId, ?string $notes = null): CashSession
    {
        return DB::transaction(function () use ($session, $countedCents, $userId, $notes): CashSession {
            $session = CashSession::query()->lockForUpdate()->findOrFail($session->getKey());

            if (! $session->isOpen()) {
                throw ValidationException::withMessages([
                    'counted_cents' => 'La caja ya está cerrada.',
                ]);
            }

            $counted = max(0, $countedCents);
            $expected = $session->expectedCents();

            $session->update([
                'status' => CashSessionStatus::Closed,
                'closed_by' => $userId,
                'closed_at' => now(),
                'expected_cents' => $expected,
                'counted_cents' => $counted,
                'difference_cents' => $counted - $expected,
                'notes' => $notes ?? $session->notes,
            ]);

            return $session->refresh();
        });
    }

    /**
     * Register a manual cash movement (deposit, withdrawal or adjustment).
     */
    public function registerCashMovement(
        CashSession $session,
        CashMovementType $type,
        int $amountCents,
        string $description,
        int $userId,
        ?string $reference = null,
    ): CashMovement {
        return DB::transaction(function () use ($session, $type, $amountCents, $description, $userId, $reference): CashMovement {
            $session = CashSession::query()->lockForUpdate()->findOrFail($session->getKey());

            if (! $session->isOpen()) {
                throw ValidationException::withMessages([
                    'amount_cents' => 'No se pueden registrar movimientos en una caja cerrada.',
                ]);
            }

            if ($type === CashMovementType::Payment) {
                throw ValidationException::withMessages([
                    'type' => 'Los cobros se registran automáticamente al recibir un pago.',
                ]);
            }

            $description = trim($description);

            if ($description === '') {
                throw ValidationException::withMessages([
                    'description' => 'Indique una descripción para el movimiento.',
                ]);
            }

            $amount = match ($type) {
                CashMovementType::Withdrawal => -abs($amountCents),
                CashMovementType::Deposit => abs($amountCents),
                default => $amountCents,
            };

            if ($amount === 0) {
                throw ValidationException::withMessages([
                    'amount_cents' => 'El importe del movimiento no puede ser cero.',
                ]);
            }

            return $session->movements()->create([
                'type' => $type,
                'amount_cents' => $amount,
                'description' => $description,
                'reference' => $reference,
                'user_id' => $userId,
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Build the receipt number for the given issue date.
     */
    private function receiptNumber(Carbon $issuedAt): string
    {
        $year = $issuedAt->format('Y');
        $prefix = config('clinic.receipt_prefix').'-'.$year;

        return $this->numbers->next('invoices:'.$year, $prefix);
    }

    /**
     * Convert a monetary amount to cents, never negative.
     */
    private function toCents(float|int|string|null $value): int
    {
        return max(0, (int) round((float) ($value ?? 0)));
    }

    /**
     * Resolve the optional catalog link of a receipt line.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveCatalog(mixed $catalogItem, mixed $productId, mixed $serviceId): array
    {
        if (is_string($catalogItem) && str_contains($catalogItem, ':')) {
            [$type, $id] = explode(':', $catalogItem, 2);

            if ($type === 'product' && ctype_digit($id)) {
                return [(int) $id, null];
            }

            if ($type === 'service' && ctype_digit($id)) {
                return [null, (int) $id];
            }
        }

        return [
            blank($productId) ? null : (int) $productId,
            blank($serviceId) ? null : (int) $serviceId,
        ];
    }
}
