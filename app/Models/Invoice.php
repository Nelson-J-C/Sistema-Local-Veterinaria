<?php

namespace App\Models;

use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Services\Format;
use Database\Factories\InvoiceFactory;
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
    'document_type',
    'number',
    'owner_id',
    'patient_id',
    'status',
    'issued_at',
    'due_at',
    'subtotal_cents',
    'discount_cents',
    'tax_cents',
    'total_cents',
    'paid_cents',
    'balance_cents',
    'notes',
    'created_by',
    'voided_at',
    'voided_by',
    'void_reason',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => InvoiceDocumentType::class,
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'paid_cents' => 'integer',
            'balance_cents' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Determine whether the receipt can still receive payments.
     */
    public function canReceivePayments(): bool
    {
        return $this->status->acceptsPayments() && $this->balance_cents > 0;
    }

    /**
     * Determine whether the receipt can be voided.
     *
     * Receipts with payments must be corrected through a refund process, so
     * they cannot be voided from the panel.
     */
    public function canBeVoided(): bool
    {
        return $this->status !== InvoiceStatus::Void && $this->paid_cents === 0;
    }

    #[Scope]
    protected function notVoided(Builder $query): Builder
    {
        return $query->where('status', '!=', InvoiceStatus::Void);
    }

    /**
     * Subtotal formatted as currency.
     */
    public function formattedSubtotal(): string
    {
        return Format::money($this->subtotal_cents);
    }

    /**
     * Discount formatted as currency.
     */
    public function formattedDiscount(): string
    {
        return Format::money($this->discount_cents);
    }

    /**
     * Tax formatted as currency.
     */
    public function formattedTax(): string
    {
        return Format::money($this->tax_cents);
    }

    /**
     * Total formatted as currency.
     */
    public function formattedTotal(): string
    {
        return Format::money($this->total_cents);
    }

    /**
     * Paid amount formatted as currency.
     */
    public function formattedPaid(): string
    {
        return Format::money($this->paid_cents);
    }

    /**
     * Balance formatted as currency.
     */
    public function formattedBalance(): string
    {
        return Format::money($this->balance_cents);
    }

    /**
     * CSS class used by the printable receipt badge.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            InvoiceStatus::Paid => 'badge-paid',
            InvoiceStatus::Void => 'badge-void',
            default => 'badge-open',
        };
    }

    /**
     * Audit financial changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'document_type',
                'number',
                'owner_id',
                'patient_id',
                'status',
                'issued_at',
                'due_at',
                'subtotal_cents',
                'discount_cents',
                'tax_cents',
                'total_cents',
                'paid_cents',
                'balance_cents',
                'notes',
                'voided_at',
                'voided_by',
                'void_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('invoice');
    }
}
