<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Proveedor')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre o razón social')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('document_number')
                            ->label('NIT o documento')
                            ->maxLength(50),
                        TextInput::make('contact_name')
                            ->label('Persona de contacto')
                            ->maxLength(150),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(150),
                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Proveedor activo')
                            ->default(true),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
