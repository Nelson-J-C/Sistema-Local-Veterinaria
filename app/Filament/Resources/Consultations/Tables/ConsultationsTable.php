<?php

namespace App\Filament\Resources\Consultations\Tables;

use App\Enums\ConsultationStatus;
use App\Filament\Resources\Consultations\Actions\ConsultationActions;
use App\Models\Consultation;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsultationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('consulted_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Consultation $record): string => $record->patient->owner->full_name)
                    ->toggleable(),
                TextColumn::make('vet.name')
                    ->label('Veterinario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chief_complaint')
                    ->label('Motivo')
                    ->limit(45)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('next_visit_at')
                    ->label('Próximo control')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('appointment.starts_at')
                    ->label('Cita')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin cita')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finalized_at')
                    ->label('Finalizada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('consulted_at', 'desc')
            ->filters([
                Filter::make('date')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('consulted_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('consulted_at', '<=', $date));
                    }),
                SelectFilter::make('vet_id')
                    ->label('Veterinario')
                    ->relationship('vet', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ConsultationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Consultation $record): bool => $record->isDraft()),
                ConsultationActions::finalize(),
                ConsultationActions::addendum(),
                DeleteAction::make()
                    ->visible(fn (Consultation $record): bool => $record->isDraft()),
            ])
            ->emptyStateHeading('Sin consultas registradas')
            ->emptyStateDescription('Cree la primera consulta desde el botón "Nueva consulta".');
    }
}
