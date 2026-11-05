<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CashMovementType: string implements HasColor, HasLabel
{
    case Payment = 'payment';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::Payment => 'Cobro',
            self::Deposit => 'Ingreso',
            self::Withdrawal => 'Retiro',
            self::Adjustment => 'Ajuste',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Payment => 'success',
            self::Deposit => 'info',
            self::Withdrawal => 'danger',
            self::Adjustment => 'warning',
        };
    }

    /**
     * Determine whether the movement adds money to the drawer.
     */
    public function isIncoming(): bool
    {
        return in_array($this, [self::Payment, self::Deposit], true);
    }
}
