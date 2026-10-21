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

    public function test_user_can_log_in_using_env_credentials(): void
    {
        config()->set('admin.email', 'admin@raciktea.test');
        config()->set('admin.password', 'correct-password');

        $this->post('/admin/login', [
            'email' => 'admin@raciktea.test',
            'password' => 'correct-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticated();

        // Baris users dibuat otomatis sebagai jangkar sesi.
        $this->assertDatabaseHas('users', ['email' => 'admin@raciktea.test']);
    }

    public function test_login_is_case_insensitive_and_uses_canonical_email(): void
    {
        config()->set('admin.email', 'Admin@RacikTea.test');
        config()->set('admin.password', 'correct-password');

        $this->post('/admin/login', [
            'email' => 'admin@raciktea.TEST',
            'password' => 'correct-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticated();

        // Baris users memakai email canonical dari env, bukan casing input.
        $this->assertDatabaseHas('users', ['email' => 'Admin@RacikTea.test']);
        $this->assertSame(1, User::count());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        config()->set('admin.email', 'admin@raciktea.test');
        config()->set('admin.password', 'correct-password');

        $this->post('/admin/login', [
            'email' => 'admin@raciktea.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fails_when_admin_env_is_not_configured(): void
    {
        config()->set('admin.email', null);
        config()->set('admin.password', null);

        $this->post('/admin/login', [
            'email' => 'admin@raciktea.test',
            'password' => 'anything',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_public_admin_setup_page_is_not_available(): void
    {
        $this->get('/admin/setup')->assertNotFound();
    }
}
