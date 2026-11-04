<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'phone',
    'password',
    'active',
    'license_number',
    'specialty',
    'color',
    'default_appointment_duration',
])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, LogsActivity, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'default_appointment_duration' => 'integer',
        ];
    }

    /**
     * Determine whether the user may access the administration panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active && ! $this->hasRole('cliente');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'vet_id');
    }

    /**
     * @return HasMany<VetTimeOff, $this>
     */
    public function vetTimeOffs(): HasMany
    {
        return $this->hasMany(VetTimeOff::class, 'user_id');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function cancelledAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'cancelled_by');
    }

    /**
     * @return HasMany<Consultation, $this>
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'vet_id');
    }

    /**
     * @return HasMany<Consultation, $this>
     */
    public function finalizedConsultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'finalized_by');
    }

    /**
     * @return HasMany<Consultation, $this>
     */
    public function createdConsultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'created_by');
    }

    /**
     * @return HasMany<PatientMeasurement, $this>
     */
    public function recordedMeasurements(): HasMany
    {
        return $this->hasMany(PatientMeasurement::class, 'recorded_by');
    }

    /**
     * Define which user fields are audited, excluding credentials and MFA secrets.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'phone',
                'active',
                'license_number',
                'specialty',
                'color',
                'default_appointment_duration',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('user');
    }
}
