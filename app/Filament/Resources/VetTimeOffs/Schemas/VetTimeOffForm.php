<?php

namespace App\Filament\Resources\VetTimeOffs\Schemas;

use App\Models\Appointment;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class VetTimeOffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bloqueo de agenda')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Veterinario')
                            ->options(fn (): array => User::query()
                                ->where('active', true)
                                ->role(['veterinario', 'admin'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('reason')
                            ->label('Motivo')
                            ->maxLength(255)
                            ->placeholder('Vacaciones, curso, licencia...'),
                        DateTimePicker::make('starts_at')
                            ->label('Desde')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(5)
                            ->displayFormat('d/m/Y H:i')
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if (blank($value) || blank($get('ends_at')) || blank($get('user_id'))) {
                                    return;
                                }

                                $overlap = Appointment::query()
                                    ->where('vet_id', (int) $get('user_id'))
                                    ->active()
                                    ->where('starts_at', '<', Carbon::parse($get('ends_at')))
                                    ->where('ends_at', '>', Carbon::parse($value))
                                    ->exists();

                                if ($overlap) {
                                    $fail('El veterinario tiene citas activas en ese rango. Cancele o reprograme primero.');
                                }
                            }),
                        DateTimePicker::make('ends_at')
                            ->label('Hasta')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(5)
                            ->displayFormat('d/m/Y H:i')
                            ->after('starts_at'),
                    ]),
            ]);
    }
}
