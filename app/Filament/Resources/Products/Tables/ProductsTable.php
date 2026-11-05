<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Actions\ProductActions;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Producto')
                    ->description(fn (Product $record): string => $record->sku)
                    ->searchable(['name', 'sku', 'barcode'])
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->placeholder('Sin categoría')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->formatStateUsing(fn (Product $record): string => $record->track_stock
                        ? $record->formattedStock()
                        : 'Sin control')
                    ->color(fn (Product $record): string => $record->stockColor())
                    ->sortable(),
                TextColumn::make('price_cents')
                    ->label('Precio')
                    ->formatStateUsing(fn (Product $record): string => $record->formattedPrice())
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
                Filter::make('low_stock')
                    ->label('Solo stock bajo')
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'products.id',
                        Product::query()->lowStock()->select('products.id'),
                    ))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ProductActions::adjustStock(),
                DeleteAction::make()
                    ->visible(fn (Product $record): bool => $record->movements_count === 0
                        && $record->batches_count === 0
                        && (auth()->user()?->can('products.delete') ?? false)),
            ])
            ->emptyStateHeading('Sin productos')
            ->emptyStateDescription('Registre medicamentos, alimentos e insumos para controlar el inventario.');
    }
}
