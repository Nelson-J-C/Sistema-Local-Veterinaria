<?php

namespace App\Filament\Resources\Vaccinations\Tables;

use App\Models\Vaccination;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VaccinationsTable
{
    public static function configure(Table $table, bool $showPatient = true): Table
    {
        return $table
            ->columns([
                TextColumn::make('administered_at')
                    ->label('Aplicada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable()
                    ->visible($showPatient),
                TextColumn::make('owner_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Vaccination $record): string => $record->patient->owner->full_name)
                    ->toggleable()
                    ->visible($showPatient),
                TextColumn::make('vaccine.name')
                    ->label('Vacuna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('next_due_at')
                    ->label('Próxima dosis')
                    ->dateTime('d/m/Y')
                    ->placeholder('-')
                    ->sortable()
                    ->color(fn (Vaccination $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('batch_number')
                    ->label('Lote')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('vet.name')
                    ->label('Veterinario')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('manufacturer')
                    ->label('Fabricante')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dose')
                    ->label('Dosis')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Registrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('administered_at', 'desc')
            ->filters([
                SelectFilter::make('patient_id')
                    ->label('Paciente')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->visible($showPatient),
                SelectFilter::make('vaccine_id')
                    ->label('Vacuna')
                    ->relationship('vaccine', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('next_due')
                    ->label('Próxima dosis')
                    ->schema([
                        DatePicker::make('due_from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('due_until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['due_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('next_due_at', '>=', $date))
                            ->when($data['due_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('next_due_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Sin vacunas registradas')
            ->emptyStateDescription('Registre la primera aplicación de vacuna del paciente.');
    }
}
