<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentScheduler;
use Closure;
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
use Illuminate\Validation\ValidationException;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
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
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }

                                $patient = Patient::query()->find($state);
                                $set('owner_id', $patient?->owner_id);
                            }),
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
                            ->live(),
                        Select::make('type')
                            ->label('Tipo de cita')
                            ->options(AppointmentType::class)
                            ->default(AppointmentType::Consultation)
                            ->required(),
                        TextInput::make('reason')
                            ->label('Motivo')
                            ->maxLength(255)
                            ->placeholder('Motivo de la consulta'),
                    ]),
                Section::make('Horario')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Inicio')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(5)
                            ->displayFormat('d/m/Y H:i')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                if (blank($state) || filled($get('ends_at'))) {
                                    return;
                                }

                                $duration = self::durationForVet($get('vet_id'));
                                $set('ends_at', Carbon::parse($state)->addMinutes($duration)->format('Y-m-d H:i:s'));
                            })
                            ->rules([
                                fn (Get $get, ?Appointment $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                    if (blank($value) || blank($get('ends_at')) || blank($get('vet_id'))) {
                                        return;
                                    }

                                    $status = $record instanceof Appointment
                                        ? $record->status
                                        : AppointmentStatus::Scheduled;

                                    try {
                                        app(AppointmentScheduler::class)->assertBookable(
                                            vetId: (int) $get('vet_id'),
                                            startsAt: Carbon::parse($value),
                                            endsAt: Carbon::parse($get('ends_at')),
                                            status: $status,
                                            ignoreAppointmentId: $record?->getKey(),
                                            originalStartsAt: $record?->starts_at,
                                        );
                                    } catch (ValidationException $exception) {
                                        foreach ($exception->errors()['starts_at'] ?? [] as $message) {
                                            $fail($message);
                                        }
                                    }
                                },
                            ]),
                        DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(5)
                            ->displayFormat('d/m/Y H:i')
                            ->after('starts_at'),
                    ]),
                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Resolve the default duration for a veterinarian.
     */
    private static function durationForVet(mixed $vetId): int
    {
        if (filled($vetId)) {
            $duration = User::query()->whereKey($vetId)->value('default_appointment_duration');

            if (filled($duration)) {
                return (int) $duration;
            }
        }

        return (int) config('clinic.appointment_duration', 30);
    }
}
