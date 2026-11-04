<?php

namespace App\Models;

use App\Enums\MedicationRoute;
use Database\Factories\PrescriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'consultation_id',
    'product_id',
    'medication_name',
    'presentation',
    'dose',
    'frequency',
    'route',
    'duration_days',
    'quantity',
    'instructions',
    'dispensed',
    'dispensed_at',
    'dispensed_by',
])]
class Prescription extends Model
{
    /** @use HasFactory<PrescriptionFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'route' => MedicationRoute::class,
            'duration_days' => 'integer',
            'quantity' => 'decimal:2',
            'dispensed' => 'boolean',
            'dispensed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    /**
     * Audit the prescribed treatment.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'consultation_id',
                'product_id',
                'medication_name',
                'presentation',
                'dose',
                'frequency',
                'route',
                'duration_days',
                'quantity',
                'instructions',
                'dispensed',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('prescription');
    }
}
