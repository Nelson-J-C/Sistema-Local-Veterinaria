<?php

namespace App\Models;

use App\Enums\ProductUnit;
use App\Services\Format;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'sku',
    'barcode',
    'name',
    'category_id',
    'supplier_id',
    'unit',
    'presentation',
    'concentration',
    'cost_cents',
    'price_cents',
    'tax_rate',
    'track_stock',
    'stock_quantity',
    'min_stock',
    'max_stock',
    'expiry_alert_days',
    'active',
    'notes',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'cost_cents' => 'integer',
            'price_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'track_stock' => 'boolean',
            'stock_quantity' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'max_stock' => 'decimal:3',
            'expiry_alert_days' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<ProductBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<PurchaseItem, $this>
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Products whose stock is controlled by the system.
     */
    #[Scope]
    protected function tracked(Builder $query): Builder
    {
        return $query->where('track_stock', true);
    }

    /**
     * Controlled products that need a restock.
     */
    #[Scope]
    protected function lowStock(Builder $query): Builder
    {
        return $query
            ->where('track_stock', true)
            ->where(function (Builder $query): void {
                $query
                    ->where('stock_quantity', '<=', 0)
                    ->orWhere(function (Builder $query): void {
                        $query->where('min_stock', '>', 0)
                            ->whereColumn('stock_quantity', '<=', 'min_stock');
                    });
            });
    }

    /**
     * Determine whether the product needs a restock.
     */
    public function isLowStock(): bool
    {
        if (! $this->track_stock) {
            return false;
        }

        return (float) $this->stock_quantity <= 0
            || ((float) $this->min_stock > 0 && (float) $this->stock_quantity <= (float) $this->min_stock);
    }

    /**
     * Determine whether the product has no stock left.
     */
    public function isOutOfStock(): bool
    {
        return $this->track_stock && (float) $this->stock_quantity <= 0;
    }

    /**
     * Color used to render the stock status in the panel.
     */
    public function stockColor(): string
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        }

        if ($this->isLowStock()) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Human readable stock status.
     */
    public function stockStatus(): string
    {
        if (! $this->track_stock) {
            return 'Sin control';
        }

        if ($this->isOutOfStock()) {
            return 'Agotado';
        }

        if ($this->isLowStock()) {
            return 'Stock bajo';
        }

        return 'Disponible';
    }

    /**
     * Stock quantity followed by the unit abbreviation.
     */
    public function formattedStock(): string
    {
        return Format::quantity((float) $this->stock_quantity).' '.$this->unit->abbreviation();
    }

    /**
     * Selling price formatted as currency.
     */
    public function formattedPrice(): string
    {
        return Format::money($this->price_cents);
    }

    /**
     * Cost formatted as currency.
     */
    public function formattedCost(): string
    {
        return Format::money($this->cost_cents);
    }

    /**
     * Audit changes to the product master data.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'sku',
                'barcode',
                'name',
                'category_id',
                'supplier_id',
                'unit',
                'presentation',
                'concentration',
                'cost_cents',
                'price_cents',
                'tax_rate',
                'track_stock',
                'min_stock',
                'max_stock',
                'expiry_alert_days',
                'active',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product');
    }
}
