<?php

namespace App\Models;

use App\Services\Format;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['code', 'name', 'category', 'description', 'price_cents', 'tax_rate', 'duration_minutes', 'active'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'tax_rate' => 'decimal:2',
            'duration_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Price formatted as currency.
     */
    public function formattedPrice(): string
    {
        return Format::money($this->price_cents);
    }

    /**
     * Tax rate formatted as a percentage.
     */
    public function formattedTaxRate(): string
    {
        return Format::percentage($this->tax_rate);
    }

    /**
     * Audit changes to the service catalog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code',
                'name',
                'category',
                'description',
                'price_cents',
                'tax_rate',
                'duration_minutes',
                'active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('service');
    }
}
