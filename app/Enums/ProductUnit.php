<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductUnit: string implements HasLabel
{
    case Unit = 'unit';
    case Box = 'box';
    case Bottle = 'bottle';
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Sachet = 'sachet';
    case Dose = 'dose';
    case Milliliter = 'ml';
    case Liter = 'l';
    case Gram = 'g';
    case Kilogram = 'kg';
    case Bag = 'bag';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unit => 'Unidad',
            self::Box => 'Caja',
            self::Bottle => 'Frasco',
            self::Tablet => 'Tableta',
            self::Capsule => 'Cápsula',
            self::Sachet => 'Sobre',
            self::Dose => 'Dosis',
            self::Milliliter => 'Mililitro (ml)',
            self::Liter => 'Litro (l)',
            self::Gram => 'Gramo (g)',
            self::Kilogram => 'Kilogramo (kg)',
            self::Bag => 'Bolsa',
        };
    }

    /**
     * Short suffix used next to quantities.
     */
    public function abbreviation(): string
    {
        return match ($this) {
            self::Unit => 'u',
            self::Box => 'caja',
            self::Bottle => 'frasco',
            self::Tablet => 'tab',
            self::Capsule => 'cap',
            self::Sachet => 'sobre',
            self::Dose => 'dosis',
            self::Milliliter => 'ml',
            self::Liter => 'l',
            self::Gram => 'g',
            self::Kilogram => 'kg',
            self::Bag => 'bolsa',
        };
    }
}
