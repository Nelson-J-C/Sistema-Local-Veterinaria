<?php

namespace App\Models;

use Database\Factories\ConsultationAddendumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'consultation_id',
    'content',
    'created_by',
])]
class ConsultationAddendum extends Model
{
    /** @use HasFactory<ConsultationAddendumFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'consultation_addenda';

    /**
     * The addendum is append-only and only records its creation time.
     */
    public const UPDATED_AT = null;

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
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Audit corrections made after a consultation is finalized.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['consultation_id', 'content'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('consultation_addendum');
    }
}
