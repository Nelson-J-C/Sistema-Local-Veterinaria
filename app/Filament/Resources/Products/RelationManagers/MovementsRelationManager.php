<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Services\Format;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Movimientos de stock';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('product'))
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->badge()
                    ->formatStateUsing(fn (StockMovement $record): string => Format::quantity((float) $record->quantity))
                    ->color(fn (StockMovement $record): string => $record->isIncoming() ? 'success' : 'danger'),
                TextColumn::make('batch.batch_number')
                    ->label('Lote')
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label('Motivo')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(StockMovementType::class),
            ])
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Los ingresos y salidas de stock aparecerán aquí.');
    }
}
