<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DiagnosisType: string implements HasColor, HasLabel
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Differential = 'differential';

    public function getLabel(): string
    {
        return match ($this) {
            self::Primary => 'Principal',
            self::Secondary => 'Secundario',
            self::Differential => 'Diferencial',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Primary => 'danger',
            self::Secondary => 'warning',
            self::Differential => 'gray',
        };
    }
}
