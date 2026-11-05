<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la compra')
                    ->columns(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Proveedor')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('document_number')
                            ->label('Factura o documento')
                            ->maxLength(100),
                        DatePicker::make('purchased_at')
                            ->label('Fecha de compra')
                            ->required()
                            ->default(fn (): string => today()->toDateString())
                            ->native(false),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Productos recibidos')
                    ->description('Cada línea genera un lote y un movimiento de ingreso al stock.')
                    ->schema([
                        Repeater::make('items')
                            ->label('Líneas de compra')
                            ->addActionLabel('Agregar producto')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(2)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Producto')
                                    ->options(fn (): array => Product::query()
                                        ->active()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('batch_number')
                                    ->label('Lote')
                                    ->maxLength(100),
                                DatePicker::make('expires_at')
                                    ->label('Caducidad')
                                    ->native(false),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.001)
                                    ->step(0.001),
                                TextInput::make('unit_cost_cents')
                                    ->label('Costo unitario')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix(config('clinic.currency_symbol'))
                                    ->formatStateUsing(fn (mixed $state): float => round(((float) $state) / 100, 2))
                                    ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100)),
                            ]),
                    ]),
            ]);
    }
}
