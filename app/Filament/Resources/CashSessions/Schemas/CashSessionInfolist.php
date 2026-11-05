<?php

namespace App\Filament\Resources\CashSessions\Schemas;

use App\Models\CashSession;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sesión de caja')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('opened_at')
                            ->label('Apertura')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('openedBy.name')
                            ->label('Abierta por')
                            ->placeholder('-'),
                        TextEntry::make('opening_cents')
                            ->label('Fondo inicial')
                            ->formatStateUsing(fn (CashSession $record): string => $record->formattedOpening()),
                        TextEntry::make('expected_cents')
                            ->label('Esperado en caja')
                            ->formatStateUsing(fn (CashSession $record): string => $record->formattedExpected())
                            ->weight('bold'),
                        TextEntry::make('movements_count')
                            ->label('Movimientos')
                            ->state(fn (CashSession $record): int => $record->movements()->count())
                            ->badge(),
                    ]),
                Section::make('Arqueo y cierre')
                    ->columns(3)
                    ->visible(fn (CashSession $record): bool => ! $record->isOpen())
                    ->schema([
                        TextEntry::make('closed_at')
                            ->label('Cierre')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('closedBy.name')
                            ->label('Cerrada por')
                            ->placeholder('-'),
                        TextEntry::make('difference_cents')
                            ->label('Diferencia')
                            ->formatStateUsing(fn (CashSession $record): string => $record->formattedDifference())
                            ->color(fn (CashSession $record): string => match (true) {
                                (int) $record->difference_cents === 0 => 'success',
                                (int) $record->difference_cents > 0 => 'warning',
                                default => 'danger',
                            })
                            ->weight('bold'),
                        TextEntry::make('counted_cents')
                            ->label('Contado')
                            ->formatStateUsing(fn (CashSession $record): string => $record->formattedCounted()),
                    ]),
                Section::make('Observaciones')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
