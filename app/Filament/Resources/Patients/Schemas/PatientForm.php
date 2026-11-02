<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Enums\Sex;
use App\Models\Owner;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        Select::make('owner_id')
                            ->label('Tutor')
                            ->relationship('owner', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Owner $record): string => $record->full_name)
                            ->searchable(['first_name', 'last_name', 'document_number', 'phone'])
                            ->preload()
                            ->required()
                            ->helperText('El tutor debe estar registrado previamente.'),
                        TextInput::make('name')
                            ->label('Nombre del paciente')
                            ->maxLength(100)
                            ->required()
                            ->autofocus(),
                        Select::make('species_id')
                            ->label('Especie')
                            ->relationship('species', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('breed_id', null)),
                        Select::make('breed_id')
                            ->label('Raza')
                            ->relationship(
                                name: 'breed',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                    ->where('species_id', $get('species_id')),
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin raza definida')
                            ->disabled(fn (Get $get): bool => blank($get('species_id')))
                            ->rule(fn (Get $get) => Rule::exists('breeds', 'id')
                                ->where('species_id', $get('species_id'))),
                        Select::make('sex')
                            ->label('Sexo')
                            ->options(Sex::class)
                            ->default(Sex::Unknown)
                            ->required(),
                        DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->maxDate(today())
                            ->displayFormat('d/m/Y'),
                        TextInput::make('color')
                            ->label('Color o seña particular')
                            ->maxLength(50),
                        TextInput::make('weight_kg')
                            ->label('Peso')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(999)
                            ->step(0.01)
                            ->suffix('kg'),
                        TextInput::make('microchip')
                            ->label('Número de microchip')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Toggle::make('sterilized')
                            ->label('Esterilizado'),
                        Toggle::make('active')
                            ->label('Paciente activo')
                            ->default(true)
                            ->helperText('Desactive el paciente cuando ya no esté en control.'),
                        DateTimePicker::make('deceased_at')
                            ->label('Fecha de fallecimiento')
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->columnSpanFull(),
                Section::make('Foto y notas')
                    ->columns(1)
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Fotografía')
                            ->image()
                            ->disk('public')
                            ->directory('patients')
                            ->maxSize(5120),
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
