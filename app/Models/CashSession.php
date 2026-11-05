<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Services\Format;
use Database\Factories\CashSessionFactory;
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
    'opened_by',
    'opened_at',
    'opening_cents',
    'closed_by',
    'closed_at',
    'expected_cents',
    'counted_cents',
    'difference_cents',
    'status',
    'notes',
])]
class CashSession extends Model
{
    /** @use HasFactory<CashSessionFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cents' => 'integer',
            'expected_cents' => 'integer',
            'counted_cents' => 'integer',
            'difference_cents' => 'integer',
            'status' => CashSessionStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @return HasMany<CashMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class)->latest('occurred_at')->latest('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Determine whether the session is still open.
     */
    public function isOpen(): bool
    {
        return $this->status === CashSessionStatus::Open;
    }

    /**
     * Money expected in the drawer.
     *
     * Closed sessions keep the value calculated at closing time.
     */
    public function expectedCents(): int
    {
        if ($this->expected_cents !== null) {
            return $this->expected_cents;
        }

        return $this->opening_cents + (int) $this->movements()->sum('amount_cents');
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', CashSessionStatus::Open);
    }

    /**
     * Opening amount formatted as currency.
     */
    public function formattedOpening(): string
    {
        return Format::money($this->opening_cents);
    }

    /**
     * Expected amount formatted as currency.
     */
    public function formattedExpected(): string
    {
        return Format::money($this->expectedCents());
    }

    /**
     * Counted amount formatted as currency.
     */
    public function formattedCounted(): string
    {
        return Format::money($this->counted_cents);
    }

    /**
     * Difference formatted as currency, including its sign.
     */
    public function formattedDifference(): string
    {
        $difference = (int) ($this->difference_cents ?? 0);

        return ($difference > 0 ? '+' : '').Format::money($difference);
    }

    /**
     * Audit cash session changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'opened_by',
                'opened_at',
                'opening_cents',
                'closed_by',
                'closed_at',
                'expected_cents',
                'counted_cents',
                'difference_cents',
                'status',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('cash_session');
    }
}
