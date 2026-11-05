<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoiceDocumentType: string implements HasLabel
{
    case Receipt = 'receipt';
    case Invoice = 'invoice';

    public function getLabel(): string
    {
        return match ($this) {
            self::Receipt => 'Recibo',
            self::Invoice => 'Factura',
        };
    }
}
