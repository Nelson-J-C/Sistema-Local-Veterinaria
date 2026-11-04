<?php

namespace App\Filament\Resources\Vaccines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VaccineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vacuna')
                    ->columns(2)
                    ->schema([
                        Select::make('species_id')
                            ->label('Especie')
                            ->relationship('species', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Todas las especies')
                            ->helperText('Déjelo vacío si la vacuna aplica a cualquier especie.'),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('manufacturer')
                            ->label('Fabricante')
                            ->maxLength(255),
                        TextInput::make('default_revaccination_days')
                            ->label('Días para la revacunación')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(3650)
                            ->suffix('días')
                            ->helperText('Se usa para calcular automáticamente la próxima dosis.'),
                        Toggle::make('active')
                            ->label('Vacuna activa')
                            ->default(true),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
