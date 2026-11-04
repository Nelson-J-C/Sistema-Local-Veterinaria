<?php

namespace App\Models;

use App\Observers\VaccinationObserver;
use Database\Factories\VaccinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'patient_id',
    'consultation_id',
    'vaccine_id',
    'vet_id',
    'administered_at',
    'next_due_at',
    'batch_number',
    'manufacturer',
    'dose',
    'notes',
    'created_by',
])]
#[ObservedBy(VaccinationObserver::class)]
class Vaccination extends Model
{
    /** @use HasFactory<VaccinationFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'administered_at' => 'datetime',
            'next_due_at' => 'datetime',
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
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * @return BelongsTo<Vaccine, $this>
     */
    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
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
     * @return MorphMany<Reminder, $this>
     */
    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'reminderable');
    }

    /**
     * Determine whether the next dose is already overdue.
     */
    public function isOverdue(): bool
    {
        return $this->next_due_at !== null && $this->next_due_at->isPast();
    }

    /**
     * Audit the vaccination record.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'patient_id',
                'consultation_id',
                'vaccine_id',
                'vet_id',
                'administered_at',
                'next_due_at',
                'batch_number',
                'manufacturer',
                'dose',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('vaccination');
    }
}
