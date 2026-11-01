<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_are_stored_with_their_declared_type(): void
    {
        Setting::set('tax_rate', 13, 'billing', 'decimal');
        Setting::set('reminder_days_before', 3, 'appointments', 'integer');
        Setting::set('portal_enabled', true, 'general', 'boolean');

        $this->assertSame(13.0, Setting::get('tax_rate', null, 'billing'));
        $this->assertSame(3, Setting::get('reminder_days_before', null, 'appointments'));
        $this->assertTrue(Setting::get('portal_enabled', null, 'general'));
    }

    public function test_a_default_value_is_returned_when_the_setting_does_not_exist(): void
    {
        $this->assertSame('fallback', Setting::get('missing_key', 'fallback'));
    }

    public function test_updating_a_setting_invalidates_the_cached_value(): void
    {
        Setting::set('clinic_name', 'Nombre anterior');

        $this->assertSame('Nombre anterior', Setting::get('clinic_name'));

        Setting::set('clinic_name', 'Nombre nuevo');

        $this->assertSame('Nombre nuevo', Setting::get('clinic_name'));
    }

    public function test_settings_are_scoped_by_group(): void
    {
        Setting::set('tax_rate', 13, 'billing', 'decimal');
        Setting::set('tax_rate', 10, 'other', 'decimal');

        $this->assertSame(13.0, Setting::get('tax_rate', null, 'billing'));
        $this->assertSame(10.0, Setting::get('tax_rate', null, 'other'));
    }
}
