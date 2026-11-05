<?php

namespace App\Models;

use App\Services\Format;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'product_id',
    'service_id',
    'description',
    'quantity',
    'unit_price_cents',
    'discount_cents',
    'tax_rate',
    'line_subtotal_cents',
    'line_tax_cents',
    'line_total_cents',
    'sort_order',
])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'line_subtotal_cents' => 'integer',
            'line_tax_cents' => 'integer',
            'line_total_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Quantity formatted without trailing zeros.
     */
    public function formattedQuantity(): string
    {
        return Format::quantity((float) $this->quantity);
    }

    /**
     * Unit price formatted as currency.
     */
    public function formattedUnitPrice(): string
    {
        return Format::money($this->unit_price_cents);
    }

    /**
     * Discount formatted as currency.
     */
    public function formattedDiscount(): string
    {
        return Format::money($this->discount_cents);
    }

    /**
     * Tax amount formatted as currency.
     */
    public function formattedTax(): string
    {
        return Format::money($this->line_tax_cents);
    }

    /**
     * Line total formatted as currency.
     */
    public function formattedLineTotal(): string
    {
        return Format::money($this->line_total_cents);
    }
}
