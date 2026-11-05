<?php

namespace App\Models;

use App\Enums\Sex;
use Database\Factories\PatientFactory;
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
    'owner_id',
    'name',
    'species_id',
    'breed_id',
    'sex',
    'birth_date',
    'color',
    'weight_kg',
    'microchip',
    'sterilized',
    'photo_path',
    'notes',
    'deceased_at',
    'active',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'birth_date' => 'date',
            'weight_kg' => 'decimal:2',
            'sterilized' => 'boolean',
            'deceased_at' => 'datetime',
            'active' => 'boolean',
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
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return BelongsTo<Breed, $this>
     */
    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /**
     * @return HasMany<PatientAlert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(PatientAlert::class);
    }

    /**
     * @return HasMany<PatientAlert, $this>
     */
    public function activeAlerts(): HasMany
    {
        return $this->alerts()->where('active', true);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<Consultation, $this>
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * @return HasMany<PatientMeasurement, $this>
     */
    public function measurements(): HasMany
    {
        return $this->hasMany(PatientMeasurement::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
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
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Audit the clinical and identifying fields of the patient.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'owner_id',
                'name',
                'species_id',
                'breed_id',
                'sex',
                'birth_date',
                'color',
                'weight_kg',
                'microchip',
                'sterilized',
                'active',
                'deceased_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('patient');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
