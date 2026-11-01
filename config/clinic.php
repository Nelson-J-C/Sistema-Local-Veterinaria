<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clinic Identity
    |--------------------------------------------------------------------------
    |
    | Default values used by seeders, receipts and the administration panel.
    | Runtime values can be overridden from the settings table.
    |
    */

    'name' => env('CLINIC_NAME', 'Clínica Veterinaria'),
    'legal_name' => env('CLINIC_LEGAL_NAME'),
    'address' => env('CLINIC_ADDRESS'),
    'phone' => env('CLINIC_PHONE'),
    'email' => env('CLINIC_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Initial Administrator
    |--------------------------------------------------------------------------
    |
    | Used by the database seeder to create the first administrator account.
    | The password is only required when the account does not exist yet.
    |
    */

    'admin_email' => env('CLINIC_ADMIN_EMAIL', 'admin@veterinaria.local'),
    'admin_password' => env('CLINIC_ADMIN_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    |
    | Amounts are stored in cents. Bolivia uses the boliviano (BOB) and a
    | 13% value added tax.
    |
    */

    'currency' => env('CLINIC_CURRENCY', 'BOB'),
    'currency_symbol' => env('CLINIC_CURRENCY_SYMBOL', 'Bs'),
    'tax_rate' => (float) env('CLINIC_TAX_RATE', 13),
    'receipt_prefix' => env('CLINIC_RECEIPT_PREFIX', 'REC'),

    /*
    |--------------------------------------------------------------------------
    | Operations
    |--------------------------------------------------------------------------
    */

    'timezone' => env('APP_TIMEZONE', 'America/La_Paz'),
    'appointment_duration' => (int) env('CLINIC_APPOINTMENT_DURATION', 30),
    'reminder_days_before' => (int) env('CLINIC_REMINDER_DAYS_BEFORE', 3),

];
