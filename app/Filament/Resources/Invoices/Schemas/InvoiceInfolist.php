<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recibo')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('number')
                            ->label('Número')
                            ->weight('bold')
                            ->placeholder('Sin número'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('issued_at')
                            ->label('Emitido')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('document_type')
                            ->label('Tipo de documento')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('due_at')
                            ->label('Vencimiento')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label('Emitido por')
                            ->placeholder('-'),
                    ]),
                Section::make('Tutor y paciente')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('owner.full_name')
                            ->label('Tutor'),
                        TextEntry::make('owner.phone')
                            ->label('Teléfono')
                            ->placeholder('-'),
                        TextEntry::make('patient.name')
                            ->label('Paciente')
                            ->placeholder('Sin paciente'),
                        TextEntry::make('patient.species.name')
                            ->label('Especie')
                            ->placeholder('-'),
                    ]),
                Section::make('Totales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('subtotal_cents')
                            ->label('Subtotal')
                            ->formatStateUsing(fn (Invoice $record): string => $record->formattedSubtotal()),
                        TextEntry::make('discount_cents')
                            ->label('Descuento')
                            ->formatStateUsing(fn (Invoice $record): string => $record->formattedDiscount()),
                        TextEntry::make('tax_cents')
                            ->label('IVA')
                            ->formatStateUsing(fn (Invoice $record): string => $record->formattedTax()),
                        TextEntry::make('total_cents')
                            ->label('Total')
                            ->formatStateUsing(fn (Invoice $record): string => $record->formattedTotal())
                            ->weight('bold'),
                        TextEntry::make('paid_cents')
                            ->label('Pagado')
                            ->formatStateUsing(fn (Invoice $record): string => $record->formattedPaid()),
                        TextEntry::make('balance_cents')
                            ->label('Saldo')
                            ->formatStateUsing(fn (Invoice $record): string => $record->formattedBalance())
                            ->weight('bold'),
                    ]),
                Section::make('Anulación')
                    ->columns(2)
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Void)
                    ->schema([
                        TextEntry::make('voided_at')
                            ->label('Anulado')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('voidedBy.name')
                            ->label('Anulado por')
                            ->placeholder('-'),
                        TextEntry::make('void_reason')
                            ->label('Motivo')
                            ->columnSpanFull(),
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
