<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    private function seedOrder(array $overrides = []): int
    {
        return DB::table('orders')->insertGetId(array_merge([
            'code' => 'RT-261022-A1IZW',
            'customer_name' => 'Pelanggan',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No. 5',
            'service' => 'Antar',
            'note' => 'Pakai gula aren',
            'subtotal' => 14000,
            'service_fee' => 3000,
            'total' => 17000,
            'status' => 'pending',
            'items' => json_encode([[
                'product_id' => 'teh-melati',
                'name' => 'Teh Melati',
                'options' => ['Ukuran' => 'Large'],
                'unit_price' => 7000,
                'quantity' => 2,
                'subtotal' => 14000,
            ]]),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_guest_cannot_view_order_detail(): void
    {
        $id = $this->seedOrder();

        $this->getJson("/admin/orders/{$id}")->assertUnauthorized();
        $this->get("/admin/orders/{$id}")->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_full_order_detail(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->seedOrder();

        $this->getJson("/admin/orders/{$id}")
            ->assertOk()
            ->assertJsonStructure([
                'id', 'code', 'customer_name', 'phone', 'address', 'service', 'note',
                'subtotal', 'service_fee', 'total', 'status', 'status_label',
                'created_at', 'updated_at',
                'items' => [['product_id', 'name', 'options', 'unit_price', 'quantity', 'subtotal']],
            ])
            ->assertJsonPath('id', $id)
            ->assertJsonPath('customer_name', 'Pelanggan')
            ->assertJsonPath('phone', '081234567890')
            ->assertJsonPath('address', 'Jl. Mawar No. 5')
            ->assertJsonPath('note', 'Pakai gula aren')
            ->assertJsonPath('subtotal', 14000)
            ->assertJsonPath('service_fee', 3000)
            ->assertJsonPath('total', 17000)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('status_label', 'Menunggu')
            ->assertJsonPath('items.0.name', 'Teh Melati')
            ->assertJsonPath('items.0.unit_price', 7000)
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('items.0.subtotal', 14000);
    }

    public function test_order_detail_returns_404_for_unknown_id(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/admin/orders/9999')->assertNotFound();
    }

    public function test_order_detail_items_are_decoded_json(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->seedOrder();

        $this->getJson("/admin/orders/{$id}")
            ->assertOk()
            ->assertJsonCount(1, 'items');
    }
}
