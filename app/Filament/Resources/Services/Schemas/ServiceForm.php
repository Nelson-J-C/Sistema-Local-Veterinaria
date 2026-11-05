<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Servicio')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->maxLength(30)
                            ->unique(ignoreRecord: true)
                            ->helperText('Código interno opcional. Ej. CONS-GEN.'),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(150),
                        Select::make('category')
                            ->label('Categoría')
                            ->options([
                                'consulta' => 'Consulta',
                                'preventivo' => 'Preventivo',
                                'estetica' => 'Estética',
                                'cirugia' => 'Cirugía',
                                'hospitalizacion' => 'Hospitalización',
                                'laboratorio' => 'Laboratorio',
                                'imagen' => 'Imagen',
                                'procedimiento' => 'Procedimiento',
                                'general' => 'General',
                            ])
                            ->default('general')
                            ->required()
                            ->searchable(),
                        TextInput::make('duration_minutes')
                            ->label('Duración')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440)
                            ->suffix('minutos')
                            ->helperText('Opcional, para referencia interna.'),
                        TextInput::make('price_cents')
                            ->label('Precio')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix(config('clinic.currency_symbol'))
                            ->formatStateUsing(fn (mixed $state): float => round(((float) $state) / 100, 2))
                            ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100)),
                        TextInput::make('tax_rate')
                            ->label('IVA')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(fn (): float => (float) config('clinic.tax_rate')),
                        Toggle::make('active')
                            ->label('Servicio activo')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
