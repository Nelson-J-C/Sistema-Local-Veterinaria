<?php

namespace App\Filament\Resources\CashSessions\Actions;

use App\Enums\CashMovementType;
use App\Models\CashSession;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class CashSessionActions
{
    /**
     * Close the session and register the arqueo.
     */
    public static function close(): Action
    {
        return Action::make('close')
            ->label('Cerrar caja')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('danger')
            ->visible(fn (CashSession $record): bool => $record->isOpen()
                && (auth()->user()?->can('cash.close') ?? false))
            ->requiresConfirmation()
            ->modalHeading('Cerrar caja y registrar arqueo')
            ->modalDescription(fn (CashSession $record): string => 'Esperado en caja: '.$record->formattedExpected().'. Cuente el efectivo real antes de confirmar.')
            ->modalSubmitActionLabel('Cerrar caja')
            ->schema([
                TextInput::make('counted_cents')
                    ->label('Efectivo contado')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix(config('clinic.currency_symbol'))
                    ->default(fn (CashSession $record): float => round($record->expectedCents() / 100, 2)),
                Textarea::make('notes')
                    ->label('Observaciones del cierre')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (CashSession $record, array $data): void {
                $data['counted_cents'] = (int) round(((float) $data['counted_cents']) * 100);

                try {
                    $closed = app(BillingService::class)->closeCashSession(
                        $record,
                        $data['counted_cents'],
                        (int) auth()->id(),
                        $data['notes'] ?? null,
                    );
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo cerrar la caja')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Caja cerrada')
                    ->body('Diferencia del arqueo: '.$closed->formattedDifference().'.')
                    ->send();
            });
    }

    /**
     * Register a manual deposit, withdrawal or adjustment.
     */
    public static function recordMovement(): Action
    {
        return Action::make('recordMovement')
            ->label('Registrar movimiento')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('warning')
            ->visible(fn (CashSession $record): bool => $record->isOpen()
                && (auth()->user()?->can('cash.open') ?? false))
            ->modalHeading('Movimiento manual de caja')
            ->modalDescription('Use retiros para sacar efectivo y ajustes para corregir diferencias. Los cobros se registran solos al recibir un pago.')
            ->modalSubmitActionLabel('Registrar movimiento')
            ->schema([
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        CashMovementType::Deposit->value => CashMovementType::Deposit->getLabel(),
                        CashMovementType::Withdrawal->value => CashMovementType::Withdrawal->getLabel(),
                        CashMovementType::Adjustment->value => CashMovementType::Adjustment->getLabel(),
                    ])
                    ->default(CashMovementType::Deposit->value)
                    ->required(),
                TextInput::make('amount_cents')
                    ->label('Importe')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix(config('clinic.currency_symbol')),
                TextInput::make('reference')
                    ->label('Referencia')
                    ->maxLength(100)
                    ->placeholder('Opcional'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (CashSession $record, array $data): void {
                try {
                    app(BillingService::class)->registerCashMovement(
                        session: $record,
                        type: CashMovementType::from((string) $data['type']),
                        amountCents: (int) round(((float) $data['amount_cents']) * 100),
                        description: (string) $data['description'],
                        userId: (int) auth()->id(),
                        reference: $data['reference'] ?? null,
                    );
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo registrar el movimiento')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Movimiento registrado')
                    ->body('El saldo esperado de la caja se actualizó.')
                    ->send();
            });
    }
}
