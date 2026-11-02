<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PatientAlertType: string implements HasLabel
{
    case Allergy = 'allergy';
    case ChronicCondition = 'chronic_condition';
    case Medication = 'medication';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Allergy => 'Alergia',
            self::ChronicCondition => 'Condición crónica',
            self::Medication => 'Medicamento',
            self::Other => 'Otra',
        };
    }
}
