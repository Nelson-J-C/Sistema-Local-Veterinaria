<?php

namespace App\Filament\Resources\Owners\Schemas;

use App\Enums\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class OwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombres')
                            ->maxLength(100)
                            ->requiredWithout('business_name')
                            ->autofocus(),
                        TextInput::make('last_name')
                            ->label('Apellidos')
                            ->maxLength(100),
                        TextInput::make('business_name')
                            ->label('Razón social')
                            ->maxLength(150)
                            ->requiredWithout('first_name')
                            ->helperText('Use este campo si el tutor es una empresa o refugio.'),
                        Select::make('document_type')
                            ->label('Tipo de documento')
                            ->options(DocumentType::class)
                            ->default(DocumentType::Ci)
                            ->required(),
                        TextInput::make('document_number')
                            ->label('Número de documento')
                            ->maxLength(30)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('document_type', $get('document_type'))
                                    ->where('document_complement', $get('document_complement')),
                            ),
                        TextInput::make('document_complement')
                            ->label('Complemento')
                            ->maxLength(10)
                            ->helperText('Ejemplo: 1A, 2B.'),
                    ])
                    ->columnSpanFull(),
                Section::make('Contacto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(30)
                            ->required(),
                        TextInput::make('phone_alt')
                            ->label('Teléfono alternativo')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(100)
                            ->default('Santa Cruz de la Sierra'),
                        TextInput::make('department')
                            ->label('Departamento')
                            ->maxLength(100)
                            ->default('Santa Cruz'),
                    ])
                    ->columnSpanFull(),
                Section::make('Portal y notas')
                    ->columns(2)
                    ->description('El portal para tutores todavía está pendiente.')
                    ->schema([
                        Toggle::make('portal_enabled')
                            ->label('Habilitado para el portal')
                            ->helperText('Se usará cuando el portal público esté disponible.'),
                        Toggle::make('active')
                            ->label('Tutor activo')
                            ->default(true),
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
