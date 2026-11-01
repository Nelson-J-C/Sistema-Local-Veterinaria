<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_the_login_page_can_be_rendered(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_active_users_can_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_inactive_users_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['active' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
