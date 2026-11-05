<?php

namespace App\Services;

class Format
{
    /**
     * Format an amount stored in cents as Bolivian currency.
     */
    public static function money(int|float|string|null $cents): string
    {
        $amount = (float) ($cents ?? 0) / 100;

        return config('clinic.currency_symbol').' '.number_format($amount, 2, '.', ',');
    }

    /**
     * Format a stock quantity without trailing zeros.
     */
    public static function quantity(int|float|string|null $quantity): string
    {
        $value = round((float) ($quantity ?? 0), 3);

        $decimals = fmod($value, 1.0) === 0.0 ? 0 : 3;

        return number_format($value, $decimals, '.', ',');
    }

    /**
     * Format a tax rate as a percentage.
     */
    public static function percentage(int|float|string|null $rate): string
    {
        return number_format((float) ($rate ?? 0), 2, '.', ',').'%';
    }
}
