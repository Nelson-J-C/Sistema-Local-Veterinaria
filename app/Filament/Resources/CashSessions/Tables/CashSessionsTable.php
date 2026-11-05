<?php

namespace App\Filament\Resources\CashSessions\Tables;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opened_at')
                    ->label('Apertura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('openedBy.name')
                    ->label('Abierta por')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('opening_cents')
                    ->label('Fondo inicial')
                    ->formatStateUsing(fn (CashSession $record): string => $record->formattedOpening())
                    ->toggleable(),
                TextColumn::make('expected_cents')
                    ->label('Esperado')
                    ->formatStateUsing(fn (CashSession $record): string => $record->formattedExpected())
                    ->weight('bold'),
                TextColumn::make('counted_cents')
                    ->label('Contado')
                    ->formatStateUsing(fn (CashSession $record): string => $record->formattedCounted())
                    ->placeholder('-'),
                TextColumn::make('difference_cents')
                    ->label('Diferencia')
                    ->formatStateUsing(fn (CashSession $record): string => $record->formattedDifference())
                    ->color(fn (CashSession $record): string => match (true) {
                        $record->difference_cents === null => 'gray',
                        (int) $record->difference_cents === 0 => 'success',
                        (int) $record->difference_cents > 0 => 'warning',
                        default => 'danger',
                    })
                    ->placeholder('-'),
                TextColumn::make('movements_count')
                    ->label('Movimientos')
                    ->counts('movements')
                    ->badge(),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(CashSessionStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('Sin sesiones de caja')
            ->emptyStateDescription('Abra la caja al iniciar la jornada para cobrar en efectivo.');
    }
}
