<?php

namespace App\Models;

use App\Enums\CashMovementType;
use App\Services\Format;
use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'cash_session_id',
    'type',
    'amount_cents',
    'description',
    'reference',
    'payment_id',
    'user_id',
    'occurred_at',
])]
class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount_cents' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CashSession, $this>
     */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Amount formatted as currency, including its sign.
     */
    public function formattedAmount(): string
    {
        $amount = (int) $this->amount_cents;

        return ($amount > 0 ? '+' : '').Format::money($amount);
    }
}
