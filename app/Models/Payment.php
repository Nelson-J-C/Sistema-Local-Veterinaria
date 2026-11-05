<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Services\Format;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'invoice_id',
    'amount_cents',
    'method',
    'reference',
    'paid_at',
    'received_by',
    'cash_session_id',
    'notes',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<CashSession, $this>
     */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /**
     * Amount formatted as currency.
     */
    public function formattedAmount(): string
    {
        return Format::money($this->amount_cents);
    }

    /**
     * Audit financial changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'invoice_id',
                'amount_cents',
                'method',
                'reference',
                'paid_at',
                'received_by',
                'cash_session_id',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('payment');
    }
}
