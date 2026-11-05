<?php

namespace App\Filament\Resources\ProductBatches;

use App\Enums\NavigationGroup;
use App\Filament\Resources\ProductBatches\Pages\ListProductBatches;
use App\Filament\Resources\ProductBatches\Tables\ProductBatchesTable;
use App\Models\ProductBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static ?string $modelLabel = 'lote';

    protected static ?string $pluralModelLabel = 'lotes';

    protected static ?string $navigationLabel = 'Lotes';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Inventory;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return ProductBatchesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'supplier']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductBatches::route('/'),
        ];
    }
}
