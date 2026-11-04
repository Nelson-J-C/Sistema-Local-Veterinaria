<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReminderType: string implements HasColor, HasLabel
{
    case Vaccination = 'vaccination';
    case Deworming = 'deworming';
    case Consultation = 'consultation';
    case Appointment = 'appointment';
    case Payment = 'payment';

    public function getLabel(): string
    {
        return match ($this) {
            self::Vaccination => 'Vacunación',
            self::Deworming => 'Desparasitación',
            self::Consultation => 'Control clínico',
            self::Appointment => 'Cita',
            self::Payment => 'Pago',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Vaccination => 'success',
            self::Deworming => 'info',
            self::Consultation => 'warning',
            self::Appointment => 'primary',
            self::Payment => 'danger',
        };
    }
}
