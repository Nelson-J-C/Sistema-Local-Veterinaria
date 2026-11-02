<?php

namespace App\Filament\Resources\Owners\Schemas;

use App\Models\Owner;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OwnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nombre completo'),
                        TextEntry::make('active')
                            ->label('Tutor activo')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('document_type')
                            ->label('Tipo de documento')
                            ->badge(),
                        TextEntry::make('document_number')
                            ->label('Número de documento')
                            ->placeholder('-'),
                        TextEntry::make('document_complement')
                            ->label('Complemento')
                            ->placeholder('-'),
                        TextEntry::make('patients_count')
                            ->label('Pacientes registrados')
                            ->state(fn (Owner $record): int => $record->patients_count ?? $record->patients()->count()),
                    ])
                    ->columnSpanFull(),
                Section::make('Contacto')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->copyable(),
                        TextEntry::make('phone_alt')
                            ->label('Teléfono alternativo')
                            ->placeholder('-'),
                        TextEntry::make('email')
                            ->label('Correo electrónico')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('address')
                            ->label('Dirección')
                            ->placeholder('-'),
                        TextEntry::make('city')
                            ->label('Ciudad')
                            ->placeholder('-'),
                        TextEntry::make('department')
                            ->label('Departamento')
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull(),
                Section::make('Portal y notas')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('portal_enabled')
                            ->label('Habilitado para el portal')
                            ->boolean(),
                        TextEntry::make('portal_terms_accepted_at')
                            ->label('Términos aceptados')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Notas internas')
                            ->placeholder('-')
                            ->columnSpanFull(),
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
                            ->visible(fn (?Owner $record): bool => $record?->trashed() ?? false),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
