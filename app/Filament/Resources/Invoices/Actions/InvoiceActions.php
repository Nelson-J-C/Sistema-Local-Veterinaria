<?php

namespace App\Filament\Resources\Invoices\Actions;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class InvoiceActions
{
    /**
     * Open the printable receipt in a new tab.
     */
    public static function print(): Action
    {
        return Action::make('print')
            ->label('Imprimir')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->url(fn (Invoice $record): string => route('invoices.receipt', $record))
            ->openUrlInNewTab()
            ->visible(fn (): bool => auth()->user()?->can('invoices.view') ?? false);
    }

    /**
     * Register a full or partial payment.
     */
    public static function recordPayment(): Action
    {
        return Action::make('recordPayment')
            ->label('Registrar pago')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (Invoice $record): bool => $record->canReceivePayments()
                && (auth()->user()?->can('payments.create') ?? false))
            ->modalHeading(fn (Invoice $record): string => 'Registrar pago del recibo '.$record->number)
            ->modalDescription(fn (Invoice $record): string => 'Saldo pendiente: '.$record->formattedBalance().'. Los pagos en efectivo requieren una caja abierta.')
            ->modalSubmitActionLabel('Registrar pago')
            ->schema([
                TextInput::make('amount_cents')
                    ->label('Importe')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix(config('clinic.currency_symbol'))
                    ->default(fn (Invoice $record): float => round($record->balance_cents / 100, 2)),
                Select::make('method')
                    ->label('Método de pago')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Cash)
                    ->required(),
                TextInput::make('reference')
                    ->label('Referencia')
                    ->maxLength(100)
                    ->placeholder('Nro. de transferencia, voucher, etc.'),
                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (Invoice $record, array $data): void {
                $data['amount_cents'] = (int) round(((float) $data['amount_cents']) * 100);

                try {
                    app(BillingService::class)->recordPayment($record, $data, (int) auth()->id());
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo registrar el pago')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Pago registrado')
                    ->body('Saldo pendiente: '.$record->formattedBalance().'.')
                    ->send();
            });
    }

    /**
     * Void an unpaid receipt with a mandatory reason.
     */
    public static function void(): Action
    {
        return Action::make('void')
            ->label('Anular recibo')
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->visible(fn (Invoice $record): bool => $record->canBeVoided()
                && (auth()->user()?->can('invoices.void') ?? false))
            ->requiresConfirmation()
            ->modalHeading(fn (Invoice $record): string => 'Anular el recibo '.$record->number)
            ->modalDescription('La anulación queda registrada con su usuario y motivo. No se puede deshacer.')
            ->modalSubmitActionLabel('Anular recibo')
            ->schema([
                Textarea::make('reason')
                    ->label('Motivo de la anulación')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (Invoice $record, array $data): void {
                try {
                    app(BillingService::class)->voidInvoice($record, (string) $data['reason'], (int) auth()->id());
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo anular el recibo')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Recibo anulado')
                    ->body('El motivo quedó registrado en la auditoría.')
                    ->send();
            });
    }
}
