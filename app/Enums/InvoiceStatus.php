<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Issued => 'Emitido',
            self::PartiallyPaid => 'Pago parcial',
            self::Paid => 'Pagado',
            self::Void => 'Anulado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'info',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
            self::Void => 'danger',
        };
    }

    /**
     * Determine whether the receipt can still receive payments.
     */
    public function acceptsPayments(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyPaid], true);
    }
}
