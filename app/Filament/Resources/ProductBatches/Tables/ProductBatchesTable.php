<?php

namespace App\Filament\Resources\ProductBatches\Tables;

use App\Models\ProductBatch;
use App\Services\Format;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('batch_number')
                    ->label('Lote')
                    ->searchable(),
                TextColumn::make('expires_at')
                    ->label('Caducidad')
                    ->date('d/m/Y')
                    ->placeholder('Sin caducidad')
                    ->description(fn (ProductBatch $record): string => $record->expiryStatus())
                    ->color(fn (ProductBatch $record): string => $record->expiryColor())
                    ->sortable(),
                TextColumn::make('quantity_remaining')
                    ->label('Disponible')
                    ->badge()
                    ->formatStateUsing(fn (ProductBatch $record): string => $record->formattedRemaining())
                    ->color(fn (ProductBatch $record): string => $record->isDepleted() ? 'gray' : 'success'),
                TextColumn::make('quantity_received')
                    ->label('Recibido')
                    ->formatStateUsing(fn (ProductBatch $record): string => Format::quantity((float) $record->quantity_received))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unit_cost_cents')
                    ->label('Costo unitario')
                    ->formatStateUsing(fn (ProductBatch $record): string => $record->formattedUnitCost()),
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('received_at')
                    ->label('Recibido el')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expires_at')
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('expiring')
                    ->label('Por vencer (30 días)')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'product_batches.id',
                        ProductBatch::query()->expiringWithin(30)->select('product_batches.id'),
                    ))
                    ->toggle(),
                Filter::make('expired')
                    ->label('Vencidos con stock')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'product_batches.id',
                        ProductBatch::query()->expired()->available()->select('product_batches.id'),
                    ))
                    ->toggle(),
                Filter::make('available')
                    ->label('Con stock disponible')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'product_batches.id',
                        ProductBatch::query()->available()->select('product_batches.id'),
                    ))
                    ->toggle(),
            ])
            ->emptyStateHeading('Sin lotes')
            ->emptyStateDescription('Registre una compra o un ajuste de stock para crear lotes.');
    }
}
