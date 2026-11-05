<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Services\Format;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'product_id',
    'batch_id',
    'type',
    'quantity',
    'unit_cost_cents',
    'reference_type',
    'reference_id',
    'notes',
    'user_id',
    'occurred_at',
])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'decimal:3',
            'unit_cost_cents' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Movements that added stock.
     */
    #[Scope]
    protected function incoming(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Movements that removed stock.
     */
    #[Scope]
    protected function outgoing(Builder $query): Builder
    {
        return $query->where('quantity', '<', 0);
    }

    /**
     * Determine whether the movement added stock.
     */
    public function isIncoming(): bool
    {
        return (float) $this->quantity > 0;
    }

    /**
     * Signed quantity formatted for display.
     */
    public function formattedQuantity(): string
    {
        $quantity = (float) $this->quantity;
        $sign = $quantity > 0 ? '+' : '';

        return $sign.Format::quantity($quantity).' '.($this->product?->unit->abbreviation() ?? '');
    }

    /**
     * Unit cost formatted for display.
     */
    public function formattedUnitCost(): string
    {
        return $this->unit_cost_cents === null
            ? '-'
            : Format::money($this->unit_cost_cents);
    }
}
