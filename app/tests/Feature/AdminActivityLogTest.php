<?php

namespace Tests\Feature;

use App\Http\Middleware\LogAdminActivity;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_action_is_logged_when_success(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/settings', [
            'store_name' => 'Toko Baru',
            'hero_heading' => 'Menu Baru',
            'hero_description' => 'Deskripsi',
            'delivery_fee' => 0,
        ]);

        $response->assertRedirect();

        $log = ActivityLog::first();
        $this->assertNotNull($log);
        $this->assertSame('admin.settings.update', $log->action);
        $this->assertStringContainsString('Toko Baru', $log->description);

        // A successful admin action must also land in the structured event log.
        $this->assertDatabaseHas('event_logs', [
            'event' => 'admin.action.success',
            'status' => 'success',
        ]);
    }

    public function test_failed_admin_action_is_not_logged_as_success(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/settings', [
            // Invalid payload → 302 redirect back with errors, no mutation.
            'store_name' => '',
        ]);

        $response->assertRedirect();

        // No row may claim the action succeeded.
        $this->assertDatabaseCount('activity_logs', 0);

        // The failed attempt must be recorded instead.
        $this->assertDatabaseHas('event_logs', [
            'event' => 'admin.action.failed',
            'status' => 'failed',
        ]);
    }

    public function test_get_request_is_not_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/orders')->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
