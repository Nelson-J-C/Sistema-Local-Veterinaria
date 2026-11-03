<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property-read int $duration_minutes
 */
#[Fillable([
    'patient_id',
    'owner_id',
    'vet_id',
    'starts_at',
    'ends_at',
    'type',
    'status',
    'reason',
    'notes',
    'created_by',
    'cancelled_at',
    'cancelled_by',
    'cancellation_reason',
    'reminder_sent_at',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'type' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_id');
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
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the duration of the appointment in minutes.
     */
    protected function durationMinutes(): Attribute
    {
        return Attribute::get(
            fn (): int => (int) $this->starts_at->diffInMinutes($this->ends_at),
        );
    }

    /**
     * Audit the fields that define the appointment and its changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'patient_id',
                'owner_id',
                'vet_id',
                'starts_at',
                'ends_at',
                'type',
                'status',
                'reason',
                'cancelled_at',
                'cancellation_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('appointment');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AppointmentStatus::Requested->value,
            AppointmentStatus::Scheduled->value,
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::CheckedIn->value,
            AppointmentStatus::InProgress->value,
        ]);
    }

    #[Scope]
    protected function forDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('starts_at', $date);
    }
}
