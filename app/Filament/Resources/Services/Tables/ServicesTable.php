<?php

namespace App\Filament\Resources\Services\Tables;

use App\Models\Service;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Servicio')
                    ->description(fn (Service $record): string => $record->code ?? '')
                    ->searchable(['name', 'code'])
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->sortable(),
                TextColumn::make('price_cents')
                    ->label('Precio')
                    ->formatStateUsing(fn (Service $record): string => $record->formattedPrice())
                    ->sortable(),
                TextColumn::make('tax_rate')
                    ->label('IVA')
                    ->formatStateUsing(fn (Service $record): string => $record->formattedTaxRate())
                    ->toggleable(),
                TextColumn::make('duration_minutes')
                    ->label('Duración')
                    ->suffix(' min')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('invoice_items_count')
                    ->label('Usos')
                    ->counts('invoiceItems')
                    ->badge(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'consulta' => 'Consulta',
                        'preventivo' => 'Preventivo',
                        'estetica' => 'Estética',
                        'cirugia' => 'Cirugía',
                        'hospitalizacion' => 'Hospitalización',
                        'laboratorio' => 'Laboratorio',
                        'imagen' => 'Imagen',
                        'procedimiento' => 'Procedimiento',
                        'general' => 'General',
                    ]),
                SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Service $record): bool => $record->invoice_items_count === 0),
            ])
            ->emptyStateHeading('Sin servicios en el catálogo')
            ->emptyStateDescription('Registre las consultas y procedimientos que cobra la clínica.');
    }
}
