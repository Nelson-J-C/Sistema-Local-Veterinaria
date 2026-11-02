<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\OwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property-read string $full_name
 */
#[Fillable([
    'first_name',
    'last_name',
    'business_name',
    'document_type',
    'document_number',
    'document_complement',
    'phone',
    'phone_alt',
    'email',
    'address',
    'city',
    'department',
    'notes',
    'portal_enabled',
    'password',
    'email_verified_at',
    'portal_terms_accepted_at',
    'active',
])]
class Owner extends Model
{
    /** @use HasFactory<OwnerFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'portal_enabled' => 'boolean',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'portal_terms_accepted_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the owner's display name, preferring the business name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->business_name
                ?: trim(implode(' ', array_filter([$this->first_name, $this->last_name]))),
        );
    }

    /**
     * @return HasMany<Patient, $this>
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * Audit only the owner fields that matter for clinic operations.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name',
                'last_name',
                'business_name',
                'document_type',
                'document_number',
                'document_complement',
                'phone',
                'phone_alt',
                'email',
                'address',
                'city',
                'department',
                'portal_enabled',
                'active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('owner');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
