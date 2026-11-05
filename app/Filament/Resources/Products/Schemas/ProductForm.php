<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductUnit;
use App\Models\Product;
use App\Services\Format;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sku')
                            ->label('Código interno (SKU)')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('barcode')
                            ->label('Código de barras')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nombre del producto')
                            ->required()
                            ->maxLength(150)
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('supplier_id')
                            ->label('Proveedor habitual')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),
                        Toggle::make('active')
                            ->label('Producto activo')
                            ->default(true),
                    ]),
                Section::make('Presentación')
                    ->columns(3)
                    ->schema([
                        Select::make('unit')
                            ->label('Unidad de medida')
                            ->options(ProductUnit::class)
                            ->default(ProductUnit::Unit)
                            ->required(),
                        TextInput::make('presentation')
                            ->label('Presentación')
                            ->maxLength(100)
                            ->placeholder('Ej. Frasco 120 ml'),
                        TextInput::make('concentration')
                            ->label('Concentración')
                            ->maxLength(100)
                            ->placeholder('Ej. 50 mg/ml'),
                    ]),
                Section::make('Precios e impuestos')
                    ->columns(3)
                    ->schema([
                        self::moneyInput('cost_cents', 'Costo'),
                        self::moneyInput('price_cents', 'Precio de venta'),
                        TextInput::make('tax_rate')
                            ->label('IVA')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(fn (): float => (float) config('clinic.tax_rate')),
                    ]),
                Section::make('Control de stock')
                    ->columns(3)
                    ->schema([
                        Toggle::make('track_stock')
                            ->label('Controlar stock')
                            ->helperText('Desactive si el producto no maneja existencias (por ejemplo, un servicio).')
                            ->default(true)
                            ->live(),
                        TextInput::make('stock_quantity')
                            ->label('Stock actual')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (mixed $state, ?Product $record): string => $record instanceof Product
                                ? $record->formattedStock()
                                : Format::quantity((float) $state))
                            ->helperText('Se modifica con compras y ajustes, no desde aquí.'),
                        TextInput::make('min_stock')
                            ->label('Stock mínimo')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.001)
                            ->default(0)
                            ->visible(fn (Get $get): bool => (bool) $get('track_stock')),
                        TextInput::make('max_stock')
                            ->label('Stock máximo')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.001)
                            ->visible(fn (Get $get): bool => (bool) $get('track_stock')),
                        TextInput::make('expiry_alert_days')
                            ->label('Aviso de caducidad')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(3650)
                            ->suffix('días')
                            ->default(90)
                            ->visible(fn (Get $get): bool => (bool) $get('track_stock')),
                    ]),
                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Money input that stores the value in cents.
     */
    private static function moneyInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->prefix(config('clinic.currency_symbol'))
            ->default(0)
            ->formatStateUsing(fn (mixed $state): float => round(((float) $state) / 100, 2))
            ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100));
    }
}
