<?php

namespace Tests\Unit;

use Tests\TestCase;

class ClinicConfigTest extends TestCase
{
    public function test_the_clinic_defaults_match_the_bolivian_configuration(): void
    {
        $this->assertSame('BOB', config('clinic.currency'));
        $this->assertSame('Bs', config('clinic.currency_symbol'));
        $this->assertSame(13.0, config('clinic.tax_rate'));
        $this->assertSame('America/La_Paz', config('clinic.timezone'));
        $this->assertSame('REC', config('clinic.receipt_prefix'));
    }
}
