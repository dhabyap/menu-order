<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_records_event_log_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/backups/now')->assertStatus(200);

        $this->assertDatabaseHas('event_logs', [
            'event' => 'backup.completed',
            'status' => 'success',
        ]);
    }

    public function test_admin_login_failure_records_event_log_entry(): void
    {
        $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('event_logs', [
            'event' => 'admin.login.failed',
            'status' => 'failed',
        ]);
    }

    public function test_admin_failed_validation_action_records_failed_event_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/settings', [
            'store_name' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_logs', [
            'event' => 'admin.action.failed',
            'status' => 'failed',
        ]);
    }

    public function test_admin_login_throttling_records_event_log_entry(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/admin/login', [
                'email' => 'wrong@example.com',
                'password' => 'password',
            ]);
        }

        $this->assertDatabaseHas('event_logs', [
            'event' => 'admin.login.throttled',
            'status' => 'failed',
        ]);
    }
}
