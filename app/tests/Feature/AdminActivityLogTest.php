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

    public function test_admin_action_is_logged(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/settings', [
            'store_name' => 'Toko Baru',
            'whatsapp_number' => '6281234567890',
        ]);

        $response->assertRedirect();

        $log = ActivityLog::first();
        $this->assertNotNull($log);
        $this->assertSame('admin.settings.update', $log->action);
        $this->assertStringContainsString('Toko Baru', $log->description);
    }

    public function test_get_request_is_not_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/orders')->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
