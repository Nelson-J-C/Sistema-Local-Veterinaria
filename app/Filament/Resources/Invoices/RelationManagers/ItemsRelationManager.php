<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\InvoiceItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Conceptos del recibo';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product', 'service']))
            ->columns([
                TextColumn::make('description')
                    ->label('Concepto')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('origin')
                    ->label('Origen')
                    ->badge()
                    ->state(fn (InvoiceItem $record): string => match (true) {
                        $record->service_id !== null => 'Servicio',
                        $record->product_id !== null => 'Producto',
                        default => 'Manual',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Servicio' => 'info',
                        'Producto' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn (InvoiceItem $record): string => $record->formattedQuantity()),
                TextColumn::make('unit_price_cents')
                    ->label('Precio unitario')
                    ->formatStateUsing(fn (InvoiceItem $record): string => $record->formattedUnitPrice()),
                TextColumn::make('discount_cents')
                    ->label('Descuento')
                    ->formatStateUsing(fn (InvoiceItem $record): string => $record->formattedDiscount()),
                TextColumn::make('line_tax_cents')
                    ->label('IVA')
                    ->formatStateUsing(fn (InvoiceItem $record): string => $record->formattedTax()),
                TextColumn::make('line_total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (InvoiceItem $record): string => $record->formattedLineTotal())
                    ->weight('bold'),
            ])
            ->defaultSort('sort_order')
            ->emptyStateHeading('Sin conceptos registrados');
    }
}
