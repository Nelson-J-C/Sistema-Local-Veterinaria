<?php

namespace App\Filament\Resources\Vaccinations\Schemas;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VaccinationForm
{
    public static function configure(Schema $schema, bool $includePatient = true, bool $includeConsultation = true): Schema
    {
        $calculateNextDue = function (Get $get, Set $set): void {
            $days = Vaccine::query()
                ->whereKey($get('vaccine_id'))
                ->value('default_revaccination_days');
            $administeredAt = $get('administered_at');

            if (blank($days) || blank($administeredAt)) {
                return;
            }

            $set(
                'next_due_at',
                Carbon::parse($administeredAt)->addDays((int) $days)->format('Y-m-d H:i:s'),
            );
        };

        return $schema
            ->components([
                Section::make('Paciente y aplicación')
                    ->columns(2)
                    ->schema([
                        Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship(
                                name: 'patient',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->with('owner'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Patient $record): string => "{$record->name} · {$record->owner->full_name}")
                            ->searchable(['name'])
                            ->preload()
                            ->required($includePatient)
                            ->visible($includePatient)
                            ->live(),
                        Select::make('consultation_id')
                            ->label('Consulta relacionada')
                            ->options(function (Get $get, ?Vaccination $record): array {
                                $patientId = $get('patient_id') ?? $record?->patient_id;

                                return Consultation::query()
                                    ->with('vet')
                                    ->when(
                                        filled($patientId),
                                        fn (Builder $query): Builder => $query->where('patient_id', $patientId),
                                    )
                                    ->orderByDesc('consulted_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Consultation $consultation): array => [
                                        $consultation->getKey() => $consultation->consulted_at->format('d/m/Y H:i')
                                            .' · '.$consultation->chief_complaint,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->placeholder('Sin consulta asociada')
                            ->helperText('Opcional. Permite vincular la aplicación con la atención clínica.')
                            ->visible($includeConsultation),
                        Select::make('vaccine_id')
                            ->label('Vacuna')
                            ->options(function (?Vaccination $record): array {
                                return Vaccine::query()
                                    ->where('active', true)
                                    ->when(
                                        $record?->vaccine_id,
                                        fn (Builder $query, int $vaccineId): Builder => $query->orWhereKey($vaccineId),
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated($calculateNextDue),
                        Select::make('vet_id')
                            ->label('Veterinario')
                            ->options(fn (): array => User::query()
                                ->where('active', true)
                                ->role(['veterinario', 'admin'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->hasRole('veterinario') ? auth()->id() : null)
                            ->searchable(),
                        DateTimePicker::make('administered_at')
                            ->label('Fecha de aplicación')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated($calculateNextDue),
                        DateTimePicker::make('next_due_at')
                            ->label('Próxima dosis')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Si se deja vacío, se calcula con los días de revacunación del catálogo.'),
                    ]),
                Section::make('Detalle de la aplicación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('batch_number')
                            ->label('Número de lote')
                            ->maxLength(100),
                        TextInput::make('manufacturer')
                            ->label('Fabricante')
                            ->maxLength(150),
                        TextInput::make('dose')
                            ->label('Dosis aplicada')
                            ->maxLength(100)
                            ->placeholder('Ej. 1 ml'),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
