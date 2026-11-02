<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasLabel
{
    case Ci = 'ci';
    case Nit = 'nit';
    case Passport = 'passport';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ci => 'Cédula de identidad',
            self::Nit => 'NIT',
            self::Passport => 'Pasaporte',
            self::Other => 'Otro',
        };
    }
}
