<?php

namespace App\Filament\Resources\Consultations\Schemas;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ConsultationForm
{
    public static function configure(Schema $schema): Schema
    {
        $isLocked = fn (?Consultation $record): bool => $record?->isLocked() ?? false;

        return $schema
            ->components([
                Section::make('Paciente y profesional')
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
                            ->required()
                            ->disabled(fn (?Consultation $record): bool => filled($record) || ($record?->isLocked() ?? false)),
                        Select::make('appointment_id')
                            ->label('Cita relacionada')
                            ->options(function (Get $get, ?Consultation $record): array {
                                $patientId = $get('patient_id') ?? $record?->patient_id;

                                return Appointment::query()
                                    ->with('patient')
                                    ->when(
                                        filled($patientId),
                                        fn (Builder $query): Builder => $query->where('patient_id', $patientId),
                                    )
                                    ->whereNotIn('status', [
                                        AppointmentStatus::Cancelled->value,
                                        AppointmentStatus::NoShow->value,
                                        AppointmentStatus::Completed->value,
                                    ])
                                    ->where(function (Builder $query) use ($record): void {
                                        $query->whereDoesntHave('consultation');

                                        if ($record?->appointment_id) {
                                            $query->orWhereKey($record->appointment_id);
                                        }
                                    })
                                    ->orderByDesc('starts_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Appointment $appointment): array => [
                                        $appointment->getKey() => $appointment->starts_at->format('d/m/Y H:i')
                                            .' · '.$appointment->patient->name
                                            .' · '.($appointment->reason ?: 'Sin motivo'),
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->live()
                            ->disabled($isLocked)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }

                                $appointment = Appointment::query()
                                    ->with('patient')
                                    ->find($state);

                                if ($appointment === null) {
                                    return;
                                }

                                $set('patient_id', $appointment->patient_id);
                                $set('vet_id', $appointment->vet_id);
                                $set('consulted_at', now()->format('Y-m-d H:i:s'));

                                if (blank($get('chief_complaint'))) {
                                    $set('chief_complaint', $appointment->reason ?: 'Consulta');
                                }
                            })
                            ->helperText('Opcional. Si selecciona una cita, se vinculan paciente y veterinario.'),
                        Select::make('vet_id')
                            ->label('Veterinario')
                            ->options(fn (): array => User::query()
                                ->where('active', true)
                                ->role(['veterinario', 'admin'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabled($isLocked),
                        DateTimePicker::make('consulted_at')
                            ->label('Fecha y hora de la consulta')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now())
                            ->disabled($isLocked),
                        TextInput::make('chief_complaint')
                            ->label('Motivo de consulta')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->disabled($isLocked),
                    ]),
                Section::make('Registro SOAP')
                    ->columns(2)
                    ->schema([
                        Textarea::make('subjective')
                            ->label('Subjetivo')
                            ->helperText('Anamnesis y lo referido por el tutor.')
                            ->rows(4)
                            ->columnSpan(1)
                            ->disabled($isLocked),
                        Textarea::make('objective')
                            ->label('Objetivo')
                            ->helperText('Hallazgos del examen físico y estudios.')
                            ->rows(4)
                            ->columnSpan(1)
                            ->disabled($isLocked),
                        Textarea::make('assessment')
                            ->label('Evaluación')
                            ->helperText('Diagnósticos y razonamiento clínico.')
                            ->rows(4)
                            ->columnSpan(1)
                            ->disabled($isLocked),
                        Textarea::make('plan')
                            ->label('Plan')
                            ->helperText('Tratamiento, controles e indicaciones.')
                            ->rows(4)
                            ->columnSpan(1)
                            ->disabled($isLocked),
                    ]),
                Section::make('Seguimiento')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('next_visit_at')
                            ->label('Próximo control')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->disabled($isLocked),
                        Select::make('status')
                            ->label('Estado')
                            ->options(ConsultationStatus::class)
                            ->default(ConsultationStatus::Draft)
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
