<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Setting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(30),
                        Toggle::make('active')
                            ->label('Cuenta activa')
                            ->default(true)
                            ->helperText('Un usuario inactivo no puede iniciar sesión en el panel.'),
                    ])
                    ->columnSpanFull(),
                Section::make('Acceso y seguridad')
                    ->columns(2)
                    ->description('La contraseña se guarda cifrada y nunca se muestra en el panel.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('En edición, déjela en blanco para conservar la contraseña actual.'),
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('name', '!=', 'cliente'),
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->helperText('El rol define los módulos y acciones disponibles.'),
                    ])
                    ->columnSpanFull(),
                Section::make('Perfil profesional')
                    ->columns(2)
                    ->schema([
                        TextInput::make('license_number')
                            ->label('Matrícula profesional')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('specialty')
                            ->label('Especialidad')
                            ->maxLength(255),
                        ColorPicker::make('color')
                            ->label('Color en la agenda')
                            ->default('#0d9488'),
                        TextInput::make('default_appointment_duration')
                            ->label('Duración de cita (minutos)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(480)
                            ->default(fn (): int => (int) Setting::get(
                                'default_duration_minutes',
                                config('clinic.appointment_duration'),
                                'appointments',
                            ))
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
