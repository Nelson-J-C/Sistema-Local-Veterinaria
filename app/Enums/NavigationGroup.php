<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    case Administration = 'Administración';
    case Clinical = 'Clínica';
    case Configuration = 'Configuración';
    case Audit = 'Auditoría';

    public function getLabel(): string
    {
        return $this->value;
    }
}
