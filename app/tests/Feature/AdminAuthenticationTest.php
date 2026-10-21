<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Pengelola')
            ->assertSee('Daftar Menu &amp; Ketersediaan Stok', false)
            ->assertSee('Riwayat Pesanan');
    }

    public function test_login_page_uses_the_racik_tea_admin_design(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('login-layout', false)
            ->assertSee('Masuk Pengelola')
            ->assertSee('Kata sandi')
            ->assertDontSee('/admin/setup');
    }

    public function test_user_can_log_in_using_existing_admin_account(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_public_admin_setup_page_is_not_available(): void
    {
        $this->get('/admin/setup')->assertNotFound();
    }
}
