<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Sex: string implements HasLabel
{
    case Male = 'male';
    case Female = 'female';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::Male => 'Macho',
            self::Female => 'Hembra',
            self::Unknown => 'Desconocido',
        };
    }
}
