<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReminderChannel: string implements HasLabel
{
    case Panel = 'panel';
    case Email = 'email';

    public function getLabel(): string
    {
        return match ($this) {
            self::Panel => 'Panel',
            self::Email => 'Correo electrónico',
        };
    }
}
