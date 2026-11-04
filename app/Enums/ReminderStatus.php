<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReminderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Dismissed = 'dismissed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Sent => 'Enviado',
            self::Dismissed => 'Descartado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Sent => 'success',
            self::Dismissed => 'gray',
        };
    }
}
