<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Purchase;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compra')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('supplier.name')
                            ->label('Proveedor')
                            ->placeholder('Sin proveedor'),
                        TextEntry::make('document_number')
                            ->label('Documento')
                            ->placeholder('-'),
                        TextEntry::make('purchased_at')
                            ->label('Fecha de compra')
                            ->date('d/m/Y'),
                        TextEntry::make('createdBy.name')
                            ->label('Registrada por')
                            ->placeholder('-'),
                    ]),
                Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('subtotal_cents')
                            ->label('Subtotal')
                            ->formatStateUsing(fn (Purchase $record): string => $record->formattedSubtotal()),
                        TextEntry::make('tax_cents')
                            ->label('IVA')
                            ->formatStateUsing(fn (Purchase $record): string => $record->formattedTax()),
                        TextEntry::make('total_cents')
                            ->label('Total')
                            ->formatStateUsing(fn (Purchase $record): string => $record->formattedTotal())
                            ->weight('bold'),
                    ]),
                Section::make('Notas')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Observaciones')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
