<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre completo'),
                        TextEntry::make('email')
                            ->label('Correo electrónico'),
                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->placeholder('-'),
                        IconEntry::make('active')
                            ->label('Cuenta activa')
                            ->boolean(),
                    ])
                    ->columnSpanFull(),
                Section::make('Perfil profesional')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('license_number')
                            ->label('Matrícula profesional')
                            ->placeholder('-'),
                        TextEntry::make('specialty')
                            ->label('Especialidad')
                            ->placeholder('-'),
                        TextEntry::make('default_appointment_duration')
                            ->label('Duración de cita')
                            ->suffix(' minutos'),
                        ColorEntry::make('color')
                            ->label('Color en la agenda')
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull(),
                Section::make('Acceso')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->placeholder('-'),
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
                            ->visible(fn (?User $record): bool => $record?->trashed() ?? false),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
