<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasColor, HasLabel
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Adjustment = 'adjustment';
    case Return = 'return';
    case Expiry = 'expiry';
    case InternalUse = 'internal_use';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchase => 'Compra',
            self::Sale => 'Venta',
            self::Adjustment => 'Ajuste',
            self::Return => 'Devolución',
            self::Expiry => 'Vencimiento',
            self::InternalUse => 'Uso interno',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Purchase => 'success',
            self::Sale => 'primary',
            self::Adjustment => 'warning',
            self::Return => 'info',
            self::Expiry => 'danger',
            self::InternalUse => 'gray',
        };
    }

    /**
     * Determine whether the movement adds stock.
     */
    public function isIncoming(): bool
    {
        return in_array($this, [self::Purchase, self::Return], true);
    }
}
