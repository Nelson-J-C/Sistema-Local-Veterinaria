<?php

namespace App\Filament\Resources\CashSessions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Apertura de caja')
                    ->description('Registre el efectivo inicial con el que comienza la jornada.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('opening_cents')
                            ->label('Fondo inicial')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix(config('clinic.currency_symbol'))
                            ->default(0)
                            ->formatStateUsing(fn (mixed $state): float => round(((float) $state) / 100, 2))
                            ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100)),
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
