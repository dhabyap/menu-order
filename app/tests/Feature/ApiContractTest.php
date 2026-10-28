<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a product row the way the admin dashboard does.
     */
    private function seedProduct(array $overrides = []): int
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Teh',
            'slug' => 'teh',
            'sort_order' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('products')->insertGetId(array_merge([
            'category_id' => $categoryId,
            'prototype_id' => 'proto-'.$categoryId,
            'name' => 'Teh Melati',
            'description' => 'Teh segar.',
            'price' => 12000,
            'options' => '[{"name":"Ukuran","options":[{"label":"Regular","price":0},{"label":"Large","price":3000}]}]',
            'labels' => '["Favorit"]',
            'available' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_store_config_returns_defaults_when_no_settings_exist(): void
    {
        $this->getJson('/api/store-config')
            ->assertOk()
            ->assertJson([
                'delivery_fee' => 0,
                'store_name' => 'Racik Tea',
                'store_tagline' => '',
                'hero_heading' => 'Pilih minuman favoritmu.',
                'marketing_badges' => [],
            ]);
    }

    public function test_store_config_reads_settings_and_splits_badges(): void
    {
        DB::table('store_settings')->insert([
            ['key' => 'delivery_fee', 'value' => '5000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'whatsapp', 'value' => '6281234567890', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_name', 'value' => 'Kedai Teh', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'marketing_badges', 'value' => "Buka setiap hari\n  Antar area pusat  \n", 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->getJson('/api/store-config')
            ->assertOk()
            ->assertJson([
                'delivery_fee' => 5000,
                'whatsapp' => '6281234567890',
                'store_name' => 'Kedai Teh',
                'marketing_badges' => ['Buka setiap hari', 'Antar area pusat'],
            ]);
    }

    public function test_store_config_falls_back_to_environment_whatsapp_when_saved_number_is_blank(): void
    {
        config()->set('store.whatsapp', '6281234567890');
        DB::table('store_settings')->insert([
            'key' => 'whatsapp',
            'value' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/store-config')
            ->assertOk()
            ->assertJson(['whatsapp' => '6281234567890']);
    }

    public function test_products_endpoint_exposes_catalog_fields(): void
    {
        $this->seedProduct();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => 'proto-1',
                'name' => 'Teh Melati',
                'desc' => 'Teh segar.',
                'category_name' => 'Teh',
                'image_url' => null,
                'groups' => [[
                    'name' => 'Ukuran',
                    'options' => [
                        ['label' => 'Regular', 'price' => 0],
                        ['label' => 'Large', 'price' => 3000],
                    ],
                ]],
                'labels' => ['Favorit'],
            ]);
    }

    public function test_products_endpoint_includes_image_url(): void
    {
        Storage::fake('public');
        $this->seedProduct(['image_path' => 'products/teh.jpg']);
        Storage::disk('public')->put('products/teh.jpg', 'binary');

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonFragment([
                'image_url' => Storage::disk('public')->url('products/teh.jpg'),
            ]);
    }

    public function test_products_endpoint_includes_available_flag(): void
    {
        $this->seedProduct(['available' => true]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonFragment([
                'available' => 1,
            ]);
    }

    public function test_unavailable_product_is_hidden_from_catalog(): void
    {
        $this->seedProduct(['available' => false]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_order_creation_returns_code_and_total(): void
    {
        $this->seedProduct();
        DB::table('store_settings')->insert([
            ['key' => 'delivery_fee', 'value' => '5000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'whatsapp', 'value' => '6281234567890', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Antar',
            'address' => 'Jalan Melati 1',
            'items' => [[
                'product_id' => 'proto-1',
                'quantity' => 2,
                'options' => ['Ukuran' => 'Large'],
            ]],
        ])->assertCreated()
            ->assertJson([
                'total' => 35000,
                'whatsapp' => '6281234567890',
            ])
            ->assertJsonStructure(['id', 'code', 'total', 'whatsapp']);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Pelanggan',
            'service' => 'Antar',
            'subtotal' => 30000,
            'service_fee' => 5000,
            'total' => 35000,
            'status' => 'pending',
        ]);
    }

    public function test_order_for_pickup_has_no_delivery_fee(): void
    {
        $this->seedProduct();
        DB::table('store_settings')->insert([
            ['key' => 'delivery_fee', 'value' => '5000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'whatsapp', 'value' => '6281234567890', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Ambil Sendiri',
            'items' => [[
                'product_id' => 'proto-1',
                'quantity' => 1,
                'options' => ['Ukuran' => 'Regular'],
            ]],
        ])->assertCreated()->assertJson(['total' => 12000]);

        $this->assertDatabaseHas('orders', ['subtotal' => 12000, 'service_fee' => 0, 'total' => 12000]);
    }

    public function test_order_is_not_saved_when_store_whatsapp_is_not_configured(): void
    {
        config()->set('store.whatsapp', '');
        $this->seedProduct();

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Ambil Sendiri',
            'items' => [[
                'product_id' => 'proto-1',
                'quantity' => 1,
                'options' => ['Ukuran' => 'Regular'],
            ]],
        ])->assertStatus(422)
            ->assertJson([
                'message' => 'Pesanan belum disimpan karena nomor WhatsApp toko belum diatur. Silakan hubungi pengelola toko.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_uses_environment_whatsapp_when_saved_number_is_blank(): void
    {
        config()->set('store.whatsapp', '6281234567890');
        $this->seedProduct();
        DB::table('store_settings')->insert([
            'key' => 'whatsapp',
            'value' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Ambil Sendiri',
            'items' => [[
                'product_id' => 'proto-1',
                'quantity' => 1,
                'options' => ['Ukuran' => 'Regular'],
            ]],
        ])->assertCreated()->assertJson(['whatsapp' => '6281234567890']);
    }

    public function test_order_rejects_delivery_without_address(): void
    {
        $this->seedProduct();

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Antar',
            'items' => [['product_id' => 'proto-1', 'quantity' => 1, 'options' => ['Ukuran' => 'Regular']]],
        ])->assertStatus(422)
            ->assertJson(['message' => 'Alamat wajib diisi untuk layanan antar.']);
    }

    public function test_order_rejects_unavailable_product(): void
    {
        $this->seedProduct(['available' => false]);

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Ambil Sendiri',
            'items' => [['product_id' => 'proto-1', 'quantity' => 1, 'options' => ['Ukuran' => 'Regular']]],
        ])->assertStatus(422)
            ->assertJson(['message' => 'Menu tidak tersedia.']);
    }

    public function test_order_rejects_missing_option_choice(): void
    {
        $this->seedProduct();

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Ambil Sendiri',
            'items' => [['product_id' => 'proto-1', 'quantity' => 1, 'options' => []]],
        ])->assertStatus(422)
            ->assertJson(['message' => 'Pilih satu opsi untuk setiap kelompok racikan.']);
    }

    public function test_order_rejects_unknown_option_label(): void
    {
        $this->seedProduct();

        $this->postJson('/api/orders', [
            'name' => 'Pelanggan',
            'phone' => '081234567890',
            'service' => 'Ambil Sendiri',
            'items' => [['product_id' => 'proto-1', 'quantity' => 1, 'options' => ['Ukuran' => 'Ekstra Besar']]],
        ])->assertStatus(422)
            ->assertJson(['message' => 'Opsi menu tidak valid.']);
    }

    public function test_order_validation_rejects_invalid_payload(): void
    {
        $this->postJson('/api/orders', [
            'name' => '',
            'phone' => '123',
            'service' => 'Kurir',
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'phone', 'service', 'items']);
    }
}
