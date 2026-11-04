<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MedicationRoute: string implements HasLabel
{
    case Oral = 'oral';
    case Topical = 'topical';
    case Injectable = 'injectable';
    case Ophthalmic = 'ophthalmic';
    case Otic = 'otic';
    case Inhalation = 'inhalation';
    case Rectal = 'rectal';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Oral => 'Oral',
            self::Topical => 'Tópica',
            self::Injectable => 'Inyectable',
            self::Ophthalmic => 'Oftálmica',
            self::Otic => 'Ótica',
            self::Inhalation => 'Inhalatoria',
            self::Rectal => 'Rectal',
            self::Other => 'Otra',
        };
    }
}
