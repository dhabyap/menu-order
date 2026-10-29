<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryQuoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function configureStore(array $overrides = []): void
    {
        $settings = array_merge([
            'store_latitude' => '-6.200000',
            'store_longitude' => '106.800000',
            'delivery_fee' => '5000',
            'delivery_fee_per_km' => '2000',
            'delivery_included_km' => '1',
            'delivery_rounding_km' => '0.5',
            'delivery_max_km' => '20',
        ], $overrides);

        foreach ($settings as $key => $value) {
            DB::table('store_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function fakeSuccessfulMapServices(int $distanceMeters = 4200): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([[
                'lat' => '-6.210000',
                'lon' => '106.810000',
                'display_name' => 'Alamat tujuan terverifikasi, Jakarta',
            ]], 200),
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => $distanceMeters]],
            ], 200),
        ]);
    }

    public function test_quote_uses_road_distance_and_server_configured_tariff(): void
    {
        $this->configureStore();
        $this->fakeSuccessfulMapServices();

        $this->postJson('/api/delivery-quote', ['address' => 'Jalan Contoh Nomor 10, Jakarta'])
            ->assertOk()
            ->assertJson([
                'distance_meters' => 4200,
                'distance_km' => 4.2,
                'fee' => 12000,
                'destination_label' => 'Alamat tujuan terverifikasi, Jakarta',
            ])
            ->assertJsonStructure(['quote_id', 'expires_at']);

        Http::assertSentCount(2);
    }

    public function test_order_uses_signed_quote_and_persists_distance_fee_snapshot(): void
    {
        $this->configureStore();
        $this->fakeSuccessfulMapServices();
        $productId = DB::table('categories')->insertGetId([
            'name' => 'Minuman', 'slug' => 'minuman', 'sort_order' => 0, 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('products')->insert([
            'category_id' => $productId, 'prototype_id' => 'delivery-test-product', 'name' => 'Teh',
            'description' => 'Test', 'price' => 10000, 'tone' => 'green', 'icon' => 'T',
            'labels' => '[]', 'options' => '[]', 'available' => true, 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('store_settings')->insert([
            ['key' => 'whatsapp', 'value' => '6281234567890', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $address = 'Jalan Contoh Nomor 10, Jakarta';
        $quote = $this->postJson('/api/delivery-quote', ['address' => $address])->assertOk()->json();

        $order = $this->postJson('/api/orders', [
            'name' => 'Pelanggan', 'phone' => '081234567890', 'address' => $address,
            'service' => 'Antar', 'delivery_quote_id' => $quote['quote_id'],
            'items' => [['product_id' => 'delivery-test-product', 'quantity' => 1, 'options' => []]],
        ])->assertCreated()->assertJson(['total' => 22000, 'service_fee' => 12000])->json();

        $stored = DB::table('orders')->where('code', $order['code'])->first();
        $this->assertSame(4200, (int) $stored->delivery_distance_meters);
        $this->assertSame(5000, (int) $stored->delivery_base_fee);
        $this->assertSame(7000, (int) $stored->delivery_variable_fee);
    }

    public function test_quote_rejects_when_store_origin_is_not_configured(): void

    {
        $this->postJson('/api/delivery-quote', ['address' => 'Jalan Contoh Nomor 10, Jakarta'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Lokasi toko belum dikonfigurasi. Hubungi pengelola toko.');

        Http::assertNothingSent();
    }

    public function test_quote_rejects_destination_beyond_configured_limit(): void
    {
        $this->configureStore(['delivery_max_km' => '3']);
        $this->fakeSuccessfulMapServices(4200);

        $this->postJson('/api/delivery-quote', ['address' => 'Jalan Contoh Nomor 10, Jakarta'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Alamat di luar jangkauan antar toko.');
    }

    public function test_quote_reports_unfound_address(): void
    {
        $this->configureStore();
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);

        $this->postJson('/api/delivery-quote', ['address' => 'zzzz no address found'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Alamat tidak ditemukan. Masukkan alamat yang lebih lengkap.');
    }
}
