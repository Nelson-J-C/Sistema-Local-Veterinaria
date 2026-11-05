<?php

namespace App\Models;

use App\Services\Format;
use Carbon\CarbonInterface;
use Database\Factories\ProductBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_id',
    'batch_number',
    'expires_at',
    'quantity_received',
    'quantity_remaining',
    'unit_cost_cents',
    'supplier_id',
    'received_at',
    'notes',
])]
class ProductBatch extends Model
{
    /** @use HasFactory<ProductBatchFactory> */
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
            'received_at' => 'date',
            'quantity_received' => 'decimal:3',
            'quantity_remaining' => 'decimal:3',
            'unit_cost_cents' => 'integer',
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
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    /**
     * Batches that still have stock available.
     */
    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->where('quantity_remaining', '>', 0);
    }

    /**
     * Batches that can still be dispensed (available and not expired).
     */
    #[Scope]
    protected function usable(Builder $query): Builder
    {
        return $query
            ->where('quantity_remaining', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', now()->toDateString());
            });
    }

    /**
     * Batches whose expiry date has already passed.
     */
    #[Scope]
    protected function expired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString());
    }

    /**
     * Batches expiring between today and the given number of days.
     */
    #[Scope]
    protected function expiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($days)->toDateString());
    }

    /**
     * Determine whether the batch is already expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isBefore(today());
    }

    /**
     * Determine whether the batch will expire inside the alert window.
     */
    public function isExpiringSoon(?int $days = null): bool
    {
        if ($this->expires_at === null || $this->isExpired()) {
            return false;
        }

        $days ??= $this->product->expiry_alert_days;

        return $this->expires_at->lessThanOrEqualTo(today()->addDays($days));
    }

    /**
     * Determine whether the batch has no stock left.
     */
    public function isDepleted(): bool
    {
        return (float) $this->quantity_remaining <= 0;
    }

    /**
     * Days remaining before the expiry date, negative when expired.
     */
    public function daysToExpire(): ?int
    {
        return $this->expires_at === null
            ? null
            : (int) today()->diffInDays($this->expires_at, false);
    }

    /**
     * Expiry label used in the panel.
     */
    public function expiryStatus(): string
    {
        if ($this->expires_at === null) {
            return 'Sin caducidad';
        }

        $days = $this->daysToExpire() ?? 0;

        if ($days < 0) {
            return 'Vencido hace '.abs($days).' días';
        }

        if ($days === 0) {
            return 'Vence hoy';
        }

        return 'Vence en '.$days.' días';
    }

    /**
     * Color used to render the expiry status in the panel.
     */
    public function expiryColor(): string
    {
        if ($this->isExpired()) {
            return 'danger';
        }

        if ($this->isExpiringSoon()) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Remaining quantity followed by the product unit.
     */
    public function formattedRemaining(): string
    {
        $unit = $this->product?->unit->abbreviation();

        return trim(Format::quantity((float) $this->quantity_remaining).' '.$unit);
    }

    /**
     * Unit cost formatted as currency.
     */
    public function formattedUnitCost(): string
    {
        return Format::money($this->unit_cost_cents);
    }

    /**
     * Expiry date formatted for display.
     */
    public function formattedExpiry(): string
    {
        return $this->expires_at instanceof CarbonInterface
            ? $this->expires_at->format('d/m/Y')
            : 'Sin caducidad';
    }
}
