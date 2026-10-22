<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    private function seedOrder(array $overrides = []): int
    {
        return DB::table('orders')->insertGetId(array_merge([
            'code' => 'RT-261022-A1IZW',
            'customer_name' => 'Pelanggan',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No. 5',
            'service' => 'Ambil Sendiri',
            'note' => 'Pakai gula aren',
            'subtotal' => 7000,
            'service_fee' => 0,
            'total' => 7000,
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

    public function test_known_phone_returns_orders_with_code_and_status_label(): void
    {
        $this->seedOrder();

        $response = $this->postJson('/api/orders/lookup', ['phone' => '081234567890']);

        $response->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.code', 'RT-261022-A1IZW')
            ->assertJsonPath('orders.0.status', 'pending')
            ->assertJsonPath('orders.0.status_label', 'Menunggu')
            ->assertJsonPath('orders.0.items.0.name', 'Teh Melati')
            ->assertJsonPath('orders.0.items.0.quantity', 2)
            ->assertJsonPath('orders.0.items.0.options.Ukuran', 'Large');
    }

    public function test_phone_in_62_format_finds_order_stored_in_08_format(): void
    {
        $this->seedOrder(['phone' => '081234567890']);

        $this->postJson('/api/orders/lookup', ['phone' => '6281234567890'])
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.code', 'RT-261022-A1IZW');
    }

    public function test_order_stored_in_62_format_is_found_by_08_phone(): void
    {
        $this->seedOrder(['phone' => '6281234567890']);

        $this->postJson('/api/orders/lookup', ['phone' => '081234567890'])
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.code', 'RT-261022-A1IZW');
    }

    public function test_unknown_phone_returns_empty_list(): void
    {
        $this->seedOrder();

        $this->postJson('/api/orders/lookup', ['phone' => '089999999999'])
            ->assertOk()
            ->assertJsonCount(0, 'orders');
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->postJson('/api/orders/lookup', ['phone' => '12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_missing_phone_is_rejected(): void
    {
        $this->postJson('/api/orders/lookup', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_response_does_not_leak_private_fields(): void
    {
        $this->seedOrder([
            'customer_name' => 'Rahasia Missions',
            'address' => 'Jl. Rahasia No. 9',
            'note' => 'Catatan rahasia',
        ]);

        $response = $this->postJson('/api/orders/lookup', ['phone' => '081234567890']);

        $response->assertOk()
            ->assertJsonMissing(['address' => 'Jl. Rahasia No. 9'])
            ->assertJsonMissing(['customer_name' => 'Rahasia Missions'])
            ->assertJsonMissing(['note' => 'Catatan rahasia'])
            ->assertJsonMissingPath('orders.0.address')
            ->assertJsonMissingPath('orders.0.customer_name')
            ->assertJsonMissingPath('orders.0.note')
            ->assertJsonMissingPath('orders.0.phone');
    }

    public function test_guest_can_use_lookup_endpoint(): void
    {
        $this->seedOrder();

        $this->postJson('/api/orders/lookup', ['phone' => '081234567890'])
            ->assertOk()
            ->assertJsonCount(1, 'orders');
    }

    public function test_orders_of_other_customers_are_not_returned(): void
    {
        $this->seedOrder(['phone' => '081234567890']);
        $this->seedOrder(['phone' => '081299999999', 'code' => 'RT-261022-OTHER']);

        $this->postJson('/api/orders/lookup', ['phone' => '081234567890'])
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.code', 'RT-261022-A1IZW');
    }
}
