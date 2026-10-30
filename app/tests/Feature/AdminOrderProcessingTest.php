<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    private function seedOrder(array $overrides = []): int
    {
        return DB::table('orders')->insertGetId(array_merge([
            'code' => 'RT-261022-A1IZW',
            'customer_name' => 'Pelanggan',
            'phone' => '081234567890',
            'address' => null,
            'service' => 'Ambil',
            'note' => null,
            'subtotal' => 7000,
            'service_fee' => 0,
            'total' => 7000,
            'status' => 'pending',
            'items' => json_encode([['name' => 'Teh Melati', 'quantity' => 1, 'unit_price' => 7000]]),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_guest_cannot_manage_orders(): void
    {
        $id = $this->seedOrder();

        $this->getJson('/admin/orders')->assertUnauthorized();
        $this->get('/admin/orders/table')->assertRedirect('/admin/login');
        $this->patchJson("/admin/orders/{$id}/status", ['status' => 'processing'])->assertUnauthorized();

        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pending']);
    }

    public function test_admin_can_change_order_status(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->seedOrder();

        $this->patchJson("/admin/orders/{$id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJson(['id' => $id, 'status' => 'processing', 'label' => 'Diproses']);

        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'processing']);

        $this->patchJson("/admin/orders/{$id}/status", ['status' => 'completed'])->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'completed']);
    }

    public function test_order_status_update_rejects_unknown_status(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->seedOrder();

        $this->patchJson("/admin/orders/{$id}/status", ['status' => 'paid'])->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'pending']);
    }

    public function test_order_status_update_requires_existing_order(): void
    {
        $this->actingAs(User::factory()->create());

        $this->patchJson('/admin/orders/9999/status', ['status' => 'processing'])->assertNotFound();
    }

    public function test_order_summary_endpoint_reports_count_and_revenue(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seedOrder(['total' => 7000]);
        $cancelled = $this->seedOrder(['code' => 'RT-261022-B2JYX', 'total' => 5000, 'status' => 'cancelled']);

        $this->getJson('/admin/orders')
            ->assertOk()
            ->assertJson(['count' => 2, 'revenue' => 7000]);

        $this->assertNotNull($cancelled);
    }

    public function test_dashboard_orders_table_renders_status_control(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seedOrder();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('data-order-status', false)
            ->assertSee('id="orders-body"', false)
            ->assertSee('Menunggu');
    }

    public function test_orders_table_fragment_lists_orders(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seedOrder();

        $this->get('/admin/orders/table')
            ->assertOk()
            ->assertSee('RT-261022-A1IZW')
            ->assertSee('Pelanggan');
    }

    public function test_admin_can_export_orders_to_csv(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seedOrder();

        $response = $this->get('/admin/orders/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }
}
