<?php

namespace Tests\Feature;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->actingAs($this->admin);
    }

    public function test_updating_a_user_records_an_activity_entry(): void
    {
        $this->admin->update(['name' => 'Administrador Renombrado']);

        $activity = Activity::query()
            ->where('log_name', 'user')
            ->where('event', 'updated')
            ->where('subject_id', $this->admin->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Administrador Renombrado', $activity->attribute_changes['attributes']['name'] ?? null);
        $this->assertTrue($activity->causer->is($this->admin));
    }

    public function test_password_changes_are_not_written_to_the_activity_log(): void
    {
        $activityCount = Activity::query()->count();

        $this->admin->update(['password' => 'AnotherSecret123!']);

        $this->assertSame($activityCount, Activity::query()->count());
    }

    public function test_administrators_can_view_the_activity_resource(): void
    {
        $this->admin->update(['name' => 'Administrador Auditado']);

        $activity = Activity::query()
            ->where('log_name', 'user')
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->get(ActivityResource::getUrl('index'))
            ->assertOk();

        Livewire::test(ListActivities::class)
            ->assertCanSeeTableRecords([$activity]);
    }

    public function test_users_without_the_activity_permission_cannot_view_the_activity_resource(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(ActivityResource::getUrl('index'))
            ->assertForbidden();
    }
}
