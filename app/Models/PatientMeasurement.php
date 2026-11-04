<?php

namespace App\Models;

use Database\Factories\PatientMeasurementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'patient_id',
    'consultation_id',
    'measured_at',
    'weight_kg',
    'temperature_c',
    'heart_rate',
    'respiratory_rate',
    'body_condition_score',
    'pain_score',
    'notes',
    'recorded_by',
])]
class PatientMeasurement extends Model
{
    /** @use HasFactory<PatientMeasurementFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
            'weight_kg' => 'decimal:2',
            'temperature_c' => 'decimal:1',
            'heart_rate' => 'integer',
            'respiratory_rate' => 'integer',
            'body_condition_score' => 'integer',
            'pain_score' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Audit the clinical measurements.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'patient_id',
                'consultation_id',
                'measured_at',
                'weight_kg',
                'temperature_c',
                'heart_rate',
                'respiratory_rate',
                'body_condition_score',
                'pain_score',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('patient_measurement');
    }
}
