<?php

namespace App\Filament\Resources\Purchases\RelationManagers;

use App\Models\PurchaseItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Productos de la compra';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('product'))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('batch_number')
                    ->label('Lote')
                    ->placeholder('-'),
                TextColumn::make('expires_at')
                    ->label('Caducidad')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn (PurchaseItem $record): string => $record->formattedQuantity()),
                TextColumn::make('unit_cost_cents')
                    ->label('Costo unitario')
                    ->formatStateUsing(fn (PurchaseItem $record): string => $record->formattedUnitCost()),
                TextColumn::make('line_total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (PurchaseItem $record): string => $record->formattedLineTotal()),
            ])
            ->defaultSort('id')
            ->emptyStateHeading('Sin líneas registradas');
    }
}
