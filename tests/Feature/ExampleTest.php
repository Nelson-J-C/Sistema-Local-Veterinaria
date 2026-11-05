<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The public root sends visitors to the administration panel.
     */
    public function test_the_root_redirects_to_the_administration_panel(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }
}
