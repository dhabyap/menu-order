<?php

namespace Tests\Feature;

use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function seedOrder(array $overrides = []): void
    {
        DB::table('orders')->insert(array_merge([
            'code' => 'RT-261028-ABC12',
            'customer_name' => 'Budi',
            'phone' => '081234567890',
            'service' => 'Antar',
            'subtotal' => 15000,
            'service_fee' => 5000,
            'total' => 20000,
            'status' => 'processing',
            'items' => json_encode([
                ['name' => 'Teh Melati', 'quantity' => 2, 'unit_price' => 5000, 'subtotal' => 10000, 'options' => ['Ukuran' => 'Large']],
                ['name' => 'Teh Lemon', 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000, 'options' => ['Gula' => 'Pekat']],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_tracking_page_shows_options_notes_address_and_timeline(): void
    {
        $this->seedOrder([
            'address' => 'Jl. Karang Layung Dalam No. 11',
            'note' => 'Tolong dibungkus rapi',
        ]);

        $this->get('/track/RT-261028-ABC12')
            ->assertOk()
            ->assertSee('Jl. Karang Layung Dalam No. 11')
            ->assertSee('Tolong dibungkus rapi')
            ->assertSee('Ukuran: Large')
            ->assertSee('track-timeline', false)
            ->assertSee('Pesanan sedang disiapkan.');
    }

    public function test_tracking_form_is_available_without_a_code(): void
    {
        $this->get('/track')
            ->assertOk()
            ->assertSee('Lacak Pesanan')
            ->assertSee('Kode pesanan ada di pesan WhatsApp');
    }

    public function test_known_code_returns_order_details_view(): void
    {
        $this->seedOrder();

        $response = $this->get('/track/RT-261028-ABC12');

        $response->assertOk()
            ->assertSee('RT-261028-ABC12')
            ->assertSee('Budi')
            ->assertSee('Antar')
            ->assertSee('Rp 20.000')
            ->assertSee(OrderStatus::label('processing'));
    }

    public function test_unknown_code_returns_friendly_not_found_view(): void
    {
        $this->seedOrder();

        $this->get('/track/RT-999999-ZZZZZ')
            ->assertOk()
            ->assertSee('Kode Tidak Ditemukan');
    }

    public function test_tracking_page_does_not_require_login(): void
    {
        $this->seedOrder();

        $this->get('/track/RT-261028-ABC12')
            ->assertOk()
            ->assertSee('RT-261028-ABC12');
    }
}
