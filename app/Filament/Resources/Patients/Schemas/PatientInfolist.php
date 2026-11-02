<?php

namespace App\Filament\Resources\Patients\Schemas;

use App\Models\Patient;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre del paciente'),
                        TextEntry::make('owner.full_name')
                            ->label('Tutor'),
                        TextEntry::make('species.name')
                            ->label('Especie'),
                        TextEntry::make('breed.name')
                            ->label('Raza')
                            ->placeholder('-'),
                        TextEntry::make('sex')
                            ->label('Sexo')
                            ->badge(),
                        TextEntry::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        TextEntry::make('color')
                            ->label('Color o seña particular')
                            ->placeholder('-'),
                        TextEntry::make('microchip')
                            ->label('Microchip')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('weight_kg')
                            ->label('Peso')
                            ->suffix(' kg')
                            ->placeholder('-'),
                        IconEntry::make('sterilized')
                            ->label('Esterilizado')
                            ->boolean(),
                        TextEntry::make('active')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('deceased_at')
                            ->label('Fecha de fallecimiento')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull(),
                Section::make('Foto y notas')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->label('Fotografía')
                            ->disk('public')
                            ->placeholder('Sin fotografía'),
                        TextEntry::make('notes')
                            ->label('Notas internas')
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull(),
                Section::make('Registro')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('deleted_at')
                            ->label('Eliminado')
                            ->dateTime()
                            ->placeholder('-')
                            ->visible(fn (?Patient $record): bool => $record?->trashed() ?? false),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
