<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AppointmentType: string implements HasLabel
{
    case Consultation = 'consultation';
    case Vaccination = 'vaccination';
    case Deworming = 'deworming';
    case Surgery = 'surgery';
    case Grooming = 'grooming';
    case Laboratory = 'laboratory';
    case FollowUp = 'follow_up';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Consultation => 'Consulta',
            self::Vaccination => 'Vacunación',
            self::Deworming => 'Desparasitación',
            self::Surgery => 'Cirugía',
            self::Grooming => 'Baño y estética',
            self::Laboratory => 'Laboratorio',
            self::FollowUp => 'Control o seguimiento',
            self::Other => 'Otro',
        };
    }
}
