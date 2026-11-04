<?php

namespace App\Filament\Resources\Vaccines\Tables;

use App\Models\Vaccine;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VaccinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vacuna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('species.name')
                    ->label('Especie')
                    ->placeholder('Todas')
                    ->sortable(),
                TextColumn::make('manufacturer')
                    ->label('Fabricante')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('default_revaccination_days')
                    ->label('Revacunación')
                    ->suffix(' días')
                    ->placeholder('-')
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('vaccinations_count')
                    ->label('Aplicaciones')
                    ->counts('vaccinations')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('species_id')
                    ->label('Especie')
                    ->relationship('species', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activas',
                        0 => 'Inactivas',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Vaccine $record): bool => ! $record->vaccinations()->exists()),
            ])
            ->emptyStateHeading('Sin vacunas en el catálogo')
            ->emptyStateDescription('Agregue las vacunas que aplica la clínica.');
    }
}
