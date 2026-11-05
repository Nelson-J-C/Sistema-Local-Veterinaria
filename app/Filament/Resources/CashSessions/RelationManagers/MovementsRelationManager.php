<?php

namespace App\Filament\Resources\CashSessions\RelationManagers;

use App\Models\CashMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Movimientos de caja';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'payment']))
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('-'),
                TextColumn::make('amount_cents')
                    ->label('Importe')
                    ->formatStateUsing(fn (CashMovement $record): string => $record->formattedAmount())
                    ->color(fn (CashMovement $record): string => $record->amount_cents >= 0 ? 'success' : 'danger')
                    ->weight('bold'),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Los cobros en efectivo y los movimientos manuales aparecen aquí.');
    }
}
