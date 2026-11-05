<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductBatch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiringBatchesWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('inventory.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lotes por vencer')
            ->description('Lotes con stock que caducan en los próximos 60 días o ya vencieron.')
            ->query(fn (): Builder => ProductBatch::query()
                ->with('product')
                ->available()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', today()->addDays(60)->toDateString())
                ->orderBy('expires_at'))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('batch_number')
                    ->label('Lote'),
                TextColumn::make('expires_at')
                    ->label('Caducidad')
                    ->date('d/m/Y')
                    ->description(fn (ProductBatch $record): string => $record->expiryStatus())
                    ->color(fn (ProductBatch $record): string => $record->expiryColor()),
                TextColumn::make('quantity_remaining')
                    ->label('Disponible')
                    ->badge()
                    ->formatStateUsing(fn (ProductBatch $record): string => $record->formattedRemaining()),
            ])
            ->recordUrl(fn (ProductBatch $record): string => ProductResource::getUrl('view', [
                'record' => $record->product_id,
            ]))
            ->emptyStateHeading('Sin lotes por vencer')
            ->emptyStateDescription('Ningún lote con stock caduca en los próximos 60 días.');
    }
}
