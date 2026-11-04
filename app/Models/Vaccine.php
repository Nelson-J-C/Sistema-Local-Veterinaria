<?php

namespace App\Models;

use Database\Factories\VaccineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['species_id', 'name', 'manufacturer', 'default_revaccination_days', 'notes', 'active'])]
class Vaccine extends Model
{
    /** @use HasFactory<VaccineFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_revaccination_days' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    /**
     * @return HasMany<Vaccination, $this>
     */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
