<?php

namespace App\Models;

use App\Enums\DiagnosisType;
use Database\Factories\ConsultationDiagnosisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'consultation_id',
    'diagnosis_id',
    'name',
    'code',
    'type',
    'notes',
])]
class ConsultationDiagnosis extends Model
{
    /** @use HasFactory<ConsultationDiagnosisFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiagnosisType::class,
        ];
    }

    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Audit the diagnosis.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['consultation_id', 'diagnosis_id', 'name', 'code', 'type', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('consultation_diagnosis');
    }
}
