<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ProductActions
{
    /**
     * Manual stock adjustment with a mandatory reason.
     */
    public static function adjustStock(): Action
    {
        return Action::make('adjustStock')
            ->label('Ajustar stock')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('warning')
            ->visible(fn (Product $record): bool => $record->track_stock
                && (auth()->user()?->can('inventory.manage') ?? false))
            ->modalHeading(fn (Product $record): string => 'Ajustar stock de '.$record->name)
            ->modalDescription('Use cantidades positivas para ingresar y negativas para descontar. Al descontar se aplica FEFO.')
            ->modalSubmitActionLabel('Registrar ajuste')
            ->schema([
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->required()
                    ->step(0.001)
                    ->helperText('Ej. 10 para ingresar, -4 para descontar.'),
                TextInput::make('batch_number')
                    ->label('Lote')
                    ->maxLength(100)
                    ->helperText('Opcional. Si se deja vacío se genera un lote de ajuste.'),
                DatePicker::make('expires_at')
                    ->label('Fecha de caducidad')
                    ->native(false),
                TextInput::make('unit_cost')
                    ->label('Costo unitario')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix(config('clinic.currency_symbol'))
                    ->helperText('Opcional. Se usa el costo actual si se deja vacío.'),
                Textarea::make('notes')
                    ->label('Motivo del ajuste')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (Product $record, array $data): void {
                $data['unit_cost_cents'] = (int) round(((float) ($data['unit_cost'] ?? 0)) * 100);
                unset($data['unit_cost']);

                app(InventoryService::class)->adjust($record, $data, (int) auth()->id());

                Notification::make()
                    ->success()
                    ->title('Stock actualizado')
                    ->body('El movimiento quedó registrado en el historial.')
                    ->send();
            });
    }
}
