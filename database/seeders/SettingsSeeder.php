<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['general', 'clinic_name', config('clinic.name'), 'string'],
            ['general', 'clinic_legal_name', config('clinic.legal_name'), 'string'],
            ['general', 'clinic_address', config('clinic.address'), 'string'],
            ['general', 'clinic_phone', config('clinic.phone'), 'string'],
            ['general', 'clinic_email', config('clinic.email'), 'string'],
            ['general', 'clinic_logo_path', null, 'string'],
            ['billing', 'currency', config('clinic.currency'), 'string'],
            ['billing', 'currency_symbol', config('clinic.currency_symbol'), 'string'],
            ['billing', 'tax_rate', config('clinic.tax_rate'), 'decimal'],
            ['billing', 'receipt_prefix', config('clinic.receipt_prefix'), 'string'],
            ['appointments', 'default_duration_minutes', config('clinic.appointment_duration'), 'integer'],
            ['appointments', 'reminder_days_before', config('clinic.reminder_days_before'), 'integer'],
            ['system', 'timezone', config('clinic.timezone'), 'string'],
        ];

        foreach ($settings as [$group, $key, $value, $type]) {
            Setting::set($key, $value, $group, $type);
        }
    }
}
