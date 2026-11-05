<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\Format;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('inventory.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos con stock bajo')
            ->description('Productos que alcanzaron su stock mínimo o están agotados.')
            ->query(fn (): Builder => Product::query()
                ->with('category')
                ->lowStock()
                ->orderBy('stock_quantity'))
            ->columns([
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->placeholder('Sin categoría'),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->formatStateUsing(fn (Product $record): string => $record->formattedStock())
                    ->color(fn (Product $record): string => $record->stockColor()),
                TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->formatStateUsing(fn (Product $record): string => Format::quantity((float) $record->min_stock)
                        .' '.$record->unit->abbreviation()),
            ])
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Stock en orden')
            ->emptyStateDescription('Ningún producto está por debajo de su mínimo.');
    }
}
