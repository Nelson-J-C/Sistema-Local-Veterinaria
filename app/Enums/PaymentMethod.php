<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasLabel
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case Qr = 'qr';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia',
            self::Qr => 'QR',
            self::Other => 'Otro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Card => 'info',
            self::Transfer => 'primary',
            self::Qr => 'warning',
            self::Other => 'gray',
        };
    }

    /**
     * Determine whether the payment requires an open cash session.
     */
    public function requiresCashSession(): bool
    {
        return $this === self::Cash;
    }
}
