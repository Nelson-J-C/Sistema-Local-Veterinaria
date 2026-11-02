<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Enums\Sex;
use App\Models\Patient;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Patient $record): string => $record->owner->full_name)
                    ->searchable(['owner.first_name', 'owner.last_name'])
                    ->sortable(['owner_id']),
                TextColumn::make('species.name')
                    ->label('Especie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('breed.name')
                    ->label('Raza')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sex')
                    ->label('Sexo')
                    ->badge(),
                TextColumn::make('birth_date')
                    ->label('Nacimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight_kg')
                    ->label('Peso')
                    ->suffix(' kg')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('microchip')
                    ->label('Microchip')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('active_alerts_count')
                    ->label('Alertas')
                    ->counts('activeAlerts')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('deceased_at')
                    ->label('Fallecimiento')
                    ->dateTime('d/m/Y')
                    ->placeholder('-')
                    ->visible(fn (?Patient $record): bool => filled($record?->deceased_at))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->visible(fn (?Patient $record): bool => $record?->trashed() ?? false)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('species_id')
                    ->label('Especie')
                    ->relationship('species', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sex')
                    ->label('Sexo')
                    ->options(Sex::class),
                SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
                TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                    RestoreBulkAction::make()
                        ->authorizeIndividualRecords('restore'),
                    ForceDeleteBulkAction::make()
                        ->authorizeIndividualRecords('forceDelete'),
                ]),
            ])
            ->emptyStateHeading('Sin pacientes registrados')
            ->emptyStateDescription('Registre un tutor y luego cree su primer paciente.');
    }
}
