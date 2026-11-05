<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Services\Format;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Producto')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('sku')
                            ->label('SKU'),
                        TextEntry::make('barcode')
                            ->label('Código de barras')
                            ->placeholder('-'),
                        TextEntry::make('category.name')
                            ->label('Categoría')
                            ->placeholder('Sin categoría'),
                        TextEntry::make('supplier.name')
                            ->label('Proveedor')
                            ->placeholder('Sin proveedor'),
                        TextEntry::make('active')
                            ->label('Estado')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ]),
                Section::make('Inventario')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('stock_quantity')
                            ->label('Stock actual')
                            ->formatStateUsing(fn (Product $record): string => $record->track_stock
                                ? $record->formattedStock()
                                : 'Sin control')
                            ->badge()
                            ->color(fn (Product $record): string => $record->stockColor()),
                        TextEntry::make('min_stock')
                            ->label('Stock mínimo')
                            ->formatStateUsing(fn (Product $record): string => Format::quantity((float) $record->min_stock)),
                        TextEntry::make('max_stock')
                            ->label('Stock máximo')
                            ->placeholder('-')
                            ->formatStateUsing(fn (Product $record): string => $record->max_stock === null
                                ? '-'
                                : Format::quantity((float) $record->max_stock)),
                        TextEntry::make('unit')
                            ->label('Unidad'),
                        TextEntry::make('presentation')
                            ->label('Presentación')
                            ->placeholder('-'),
                        TextEntry::make('concentration')
                            ->label('Concentración')
                            ->placeholder('-'),
                        TextEntry::make('expiry_alert_days')
                            ->label('Aviso de caducidad')
                            ->suffix(' días'),
                    ]),
                Section::make('Precios')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('cost_cents')
                            ->label('Costo')
                            ->formatStateUsing(fn (Product $record): string => $record->formattedCost()),
                        TextEntry::make('price_cents')
                            ->label('Precio de venta')
                            ->formatStateUsing(fn (Product $record): string => $record->formattedPrice()),
                        TextEntry::make('tax_rate')
                            ->label('IVA')
                            ->formatStateUsing(fn (Product $record): string => Format::percentage($record->tax_rate)),
                    ]),
                Section::make('Notas')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Observaciones')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
