<?php

namespace App\Models;

use App\Services\Format;
use Database\Factories\PurchaseItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_id',
    'product_id',
    'batch_number',
    'expires_at',
    'quantity',
    'unit_cost_cents',
    'line_total_cents',
])]
class PurchaseItem extends Model
{
    /** @use HasFactory<PurchaseItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'quantity' => 'decimal:3',
            'unit_cost_cents' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Quantity followed by the product unit.
     */
    public function formattedQuantity(): string
    {
        $unit = $this->product?->unit->abbreviation();

        return trim(Format::quantity((float) $this->quantity).' '.$unit);
    }

    /**
     * Unit cost formatted as currency.
     */
    public function formattedUnitCost(): string
    {
        return Format::money($this->unit_cost_cents);
    }

    /**
     * Line total formatted as currency.
     */
    public function formattedLineTotal(): string
    {
        return Format::money($this->line_total_cents);
    }
}
