<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Solicitada',
            self::Scheduled => 'Agendada',
            self::Confirmed => 'Confirmada',
            self::CheckedIn => 'En sala de espera',
            self::InProgress => 'En atención',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
            self::NoShow => 'No asistió',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'gray',
            self::Scheduled => 'info',
            self::Confirmed => 'success',
            self::CheckedIn => 'warning',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'danger',
        };
    }

    /**
     * Determine whether the appointment still occupies the veterinarian's agenda.
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::Requested,
            self::Scheduled,
            self::Confirmed,
            self::CheckedIn,
            self::InProgress,
        ], true);
    }
}
