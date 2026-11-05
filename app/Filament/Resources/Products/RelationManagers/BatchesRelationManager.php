<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductBatch;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';

    protected static ?string $title = 'Lotes y caducidades';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('batch_number')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('product'))
            ->columns([
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
                    ->formatStateUsing(fn (ProductBatch $record): string => $record->formattedRemaining()),
                TextColumn::make('quantity_received')
                    ->label('Recibido')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(),
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
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expires_at')
            ->emptyStateHeading('Sin lotes registrados')
            ->emptyStateDescription('Los lotes se crean al registrar una compra o un ajuste de stock.');
    }
}
