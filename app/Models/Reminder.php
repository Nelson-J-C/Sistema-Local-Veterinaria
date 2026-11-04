<?php

namespace App\Models;

use App\Enums\ReminderChannel;
use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'reminderable_type',
    'reminderable_id',
    'owner_id',
    'patient_id',
    'type',
    'due_at',
    'channel',
    'status',
    'sent_at',
    'error',
    'notes',
])]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReminderType::class,
            'channel' => ReminderChannel::class,
            'status' => ReminderStatus::class,
            'due_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reminderable(): MorphTo
    {
        return $this->morphTo();
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

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', ReminderStatus::Pending);
    }

    #[Scope]
    protected function due(Builder $query): Builder
    {
        return $query->where('due_at', '<=', now()->endOfDay());
    }

    /**
     * Mark an internal reminder as handled.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * Discard a reminder that no longer applies.
     */
    public function dismiss(): void
    {
        $this->update(['status' => ReminderStatus::Dismissed]);
    }

    /**
     * Determine whether the reminder is pending and already due.
     */
    public function isOverdue(): bool
    {
        return $this->status === ReminderStatus::Pending && $this->due_at->isPast();
    }

    /**
     * Audit the reminder lifecycle.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'sent_at', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('reminder');
    }
}
