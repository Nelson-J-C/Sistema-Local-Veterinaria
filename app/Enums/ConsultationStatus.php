<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConsultationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Amended = 'amended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Finalized => 'Finalizada',
            self::Amended => 'Con addendum',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Finalized => 'success',
            self::Amended => 'info',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isLocked(): bool
    {
        return ! $this->isDraft();
    }
}
