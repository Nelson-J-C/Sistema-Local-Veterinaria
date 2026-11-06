<?php

namespace App\Filament\Widgets;

use App\Enums\CashSessionStatus;
use App\Enums\InvoiceStatus;
use App\Models\CashSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Format;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return auth()->user()?->can('invoices.view') ?? false;
    }

    protected function getStats(): array
    {
        $paymentsToday = Payment::query()
            ->whereDate('paid_at', today())
            ->get(['amount_cents']);

        $pending = Invoice::query()
            ->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid])
            ->where('balance_cents', '>', 0)
            ->sum('balance_cents');

        $issuedToday = Invoice::query()
            ->whereDate('issued_at', today())
            ->where('status', '!=', InvoiceStatus::Void)
            ->count();

        $openSession = CashSession::query()
            ->where('status', CashSessionStatus::Open)
            ->first();

        return [
            Stat::make('Cobrado hoy', Format::money((int) $paymentsToday->sum('amount_cents')))
                ->description($paymentsToday->count().' pagos registrados')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Por cobrar', Format::money((int) $pending))
                ->description('Saldo de recibos emitidos')
                ->descriptionIcon(Heroicon::OutlinedReceiptPercent)
                ->color('warning'),
            Stat::make('Recibos de hoy', (string) $issuedToday)
                ->description('Recibos emitidos hoy')
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('info'),
            Stat::make('Caja', $openSession === null ? 'Cerrada' : 'Abierta')
                ->description($openSession === null
                    ? 'Sin sesión de caja activa'
                    : 'Esperado: '.Format::money($openSession->expectedCents()))
                ->descriptionIcon(Heroicon::OutlinedWallet)
                ->color($openSession === null ? 'gray' : 'success'),
        ];
    }
}
