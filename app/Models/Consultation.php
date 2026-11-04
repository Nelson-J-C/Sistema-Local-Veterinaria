<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'patient_id',
    'appointment_id',
    'vet_id',
    'consulted_at',
    'chief_complaint',
    'subjective',
    'objective',
    'assessment',
    'plan',
    'status',
    'finalized_at',
    'finalized_by',
    'next_visit_at',
    'created_by',
])]
class Consultation extends Model
{
    /** @use HasFactory<ConsultationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consulted_at' => 'datetime',
            'status' => ConsultationStatus::class,
            'finalized_at' => 'datetime',
            'next_visit_at' => 'datetime',
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
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
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
    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * @return HasMany<ConsultationAddendum, $this>
     */
    public function addenda(): HasMany
    {
        return $this->hasMany(ConsultationAddendum::class);
    }

    /**
     * @return HasMany<PatientMeasurement, $this>
     */
    public function measurements(): HasMany
    {
        return $this->hasMany(PatientMeasurement::class);
    }

    /**
     * @return HasMany<ConsultationDiagnosis, $this>
     */
    public function diagnoses(): HasMany
    {
        return $this->hasMany(ConsultationDiagnosis::class);
    }

    /**
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return HasMany<Vaccination, $this>
     */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    /**
     * @return HasMany<Deworming, $this>
     */
    public function dewormings(): HasMany
    {
        return $this->hasMany(Deworming::class);
    }

    /**
     * Determine whether the consultation is still editable.
     */
    public function isDraft(): bool
    {
        return $this->status === ConsultationStatus::Draft;
    }

    /**
     * Determine whether the clinical record is locked for editing.
     */
    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Audit the clinical content and its lifecycle.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'patient_id',
                'appointment_id',
                'vet_id',
                'consulted_at',
                'chief_complaint',
                'subjective',
                'objective',
                'assessment',
                'plan',
                'status',
                'finalized_at',
                'finalized_by',
                'next_visit_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('consultation');
    }
}
