<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Sales grouped by issue date.
     *
     * Voided receipts are excluded because they no longer represent revenue.
     *
     * @return Collection<int, array{
     *     date: string,
     *     label: string,
     *     receipts: int,
     *     subtotal_cents: int,
     *     discount_cents: int,
     *     tax_cents: int,
     *     total_cents: int,
     *     paid_cents: int,
     *     balance_cents: int
     * }>
     */
    public function salesByDay(Carbon $from, Carbon $until): Collection
    {
        $invoices = Invoice::query()
            ->where('status', '!=', InvoiceStatus::Void)
            ->whereBetween('issued_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->get([
                'issued_at',
                'subtotal_cents',
                'discount_cents',
                'tax_cents',
                'total_cents',
                'paid_cents',
                'balance_cents',
            ]);

        return $invoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->issued_at->toDateString())
            ->map(fn (Collection $group, string $date): array => [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('es')->translatedFormat('D d/m'),
                'receipts' => $group->count(),
                'subtotal_cents' => (int) $group->sum('subtotal_cents'),
                'discount_cents' => (int) $group->sum('discount_cents'),
                'tax_cents' => (int) $group->sum('tax_cents'),
                'total_cents' => (int) $group->sum('total_cents'),
                'paid_cents' => (int) $group->sum('paid_cents'),
                'balance_cents' => (int) $group->sum('balance_cents'),
            ])
            ->sortKeys()
            ->values();
    }

    /**
     * Sum the columns of the daily sales rows.
     *
     * @param  Collection<int, array{
     *     date: string,
     *     label: string,
     *     receipts: int,
     *     subtotal_cents: int,
     *     discount_cents: int,
     *     tax_cents: int,
     *     total_cents: int,
     *     paid_cents: int,
     *     balance_cents: int
     * }>  $rows
     * @return array{receipts: int, subtotal_cents: int, discount_cents: int, tax_cents: int, total_cents: int, paid_cents: int, balance_cents: int}
     */
    public function salesTotals(Collection $rows): array
    {
        return [
            'receipts' => (int) $rows->sum('receipts'),
            'subtotal_cents' => (int) $rows->sum('subtotal_cents'),
            'discount_cents' => (int) $rows->sum('discount_cents'),
            'tax_cents' => (int) $rows->sum('tax_cents'),
            'total_cents' => (int) $rows->sum('total_cents'),
            'paid_cents' => (int) $rows->sum('paid_cents'),
            'balance_cents' => (int) $rows->sum('balance_cents'),
        ];
    }

    /**
     * Payments received in the period, grouped by method.
     *
     * @return Collection<int, array{method: PaymentMethod, label: string, payments: int, amount_cents: int}>
     */
    public function paymentsByMethod(Carbon $from, Carbon $until): Collection
    {
        $payments = Payment::query()
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->get(['method', 'amount_cents']);

        return $payments
            ->groupBy(fn (Payment $payment): string => $payment->method->value)
            ->map(fn (Collection $group, string $method): array => [
                'method' => PaymentMethod::from($method),
                'label' => PaymentMethod::from($method)->getLabel(),
                'payments' => $group->count(),
                'amount_cents' => (int) $group->sum('amount_cents'),
            ])
            ->sortByDesc('amount_cents')
            ->values();
    }

    /**
     * Appointments in the period grouped by status.
     *
     * @return Collection<int, array{status: AppointmentStatus, label: string, appointments: int}>
     */
    public function appointmentsByStatus(Carbon $from, Carbon $until, ?int $vetId = null): Collection
    {
        $appointments = Appointment::query()
            ->whereBetween('starts_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->when($vetId, fn ($query, int $id) => $query->where('vet_id', $id))
            ->get(['status']);

        return $appointments
            ->groupBy(fn (Appointment $appointment): string => $appointment->status->value)
            ->map(fn (Collection $group, string $status): array => [
                'status' => AppointmentStatus::from($status),
                'label' => AppointmentStatus::from($status)->getLabel(),
                'appointments' => $group->count(),
            ])
            ->sortByDesc('appointments')
            ->values();
    }

    /**
     * Appointments in the period grouped by veterinarian.
     *
     * @return Collection<int, array{vet: string, appointments: int}>
     */
    public function appointmentsByVet(Carbon $from, Carbon $until, ?int $vetId = null): Collection
    {
        $appointments = Appointment::query()
            ->with('vet')
            ->whereBetween('starts_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->when($vetId, fn ($query, int $id) => $query->where('vet_id', $id))
            ->get(['vet_id']);

        return $appointments
            ->groupBy('vet_id')
            ->map(fn (Collection $group): array => [
                'vet' => $group->first()->vet->name,
                'appointments' => $group->count(),
            ])
            ->sortByDesc('appointments')
            ->values();
    }

    /**
     * Products at or below their minimum stock.
     *
     * @return EloquentCollection<int, Product>
     */
    public function lowStockProducts(): EloquentCollection
    {
        return Product::query()
            ->with('category')
            ->lowStock()
            ->orderBy('stock_quantity')
            ->get();
    }

    /**
     * Batches with stock that expire inside the alert window.
     *
     * @return EloquentCollection<int, ProductBatch>
     */
    public function expiringBatches(int $days = 60): EloquentCollection
    {
        return ProductBatch::query()
            ->with('product')
            ->available()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', today()->addDays($days)->toDateString())
            ->orderBy('expires_at')
            ->get();
    }
}
