<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_menu_and_configure_store_settings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/products', [
            'name' => 'Teh Melati',
            'category' => 'Teh',
            'price' => 12000,
            'description' => 'Teh segar.',
            'labels' => 'Favorit',
            'options_json' => '[{"name":"Ukuran","options":[{"label":"Regular","price":0},{"label":"Large","price":3000}]}]',
        ])->assertRedirect();

        $product = DB::table('products')->first();
        $this->assertSame('Teh Melati', $product->name);
        $this->assertNotEmpty($product->prototype_id);
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Teh Melati')
            ->assertSee('action-stock', false)
            ->assertSee('Edit Teh Melati')
            ->assertSee('Tambah Menu Baru')
            ->assertSee('add-option-group', false)
            ->assertSee('id="options-json"', false);

        $this->post('/admin/settings', [
            'whatsapp' => '6281234567890',
            'delivery_fee' => 5000,
            'store_name' => 'Kedai Teh',
            'store_tagline' => 'Teh harian',
            'hero_heading' => 'Menu teh kami',
            'hero_description' => 'Pilih racikan favorit.',
            'marketing_badges' => "Buka setiap hari\nAntar area pusat",
        ])->assertRedirect();

        $this->getJson('/api/store-config')
            ->assertOk()
            ->assertJson([
                'whatsapp' => '6281234567890',
                'delivery_fee' => 5000,
                'store_name' => 'Kedai Teh',
                'marketing_badges' => ['Buka setiap hari', 'Antar area pusat'],
            ]);
        $this->getJson('/api/products')->assertOk()->assertJsonFragment(['name' => 'Teh Melati']);

        $this->put('/admin/products/'.$product->id, [
            'name' => 'Teh Melati Large',
            'category' => 'Teh',
            'price' => 14000,
            'description' => 'Porsi besar.',
            'labels' => 'Favorit, Baru',
            'options_json' => '[]',
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Teh Melati Large', 'price' => 14000]);

        $this->patch('/admin/products/'.$product->id)->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'available' => false]);
        $this->delete('/admin/products/'.$product->id)->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_order_total_uses_menu_option_price_and_saved_delivery_fee(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Teh',
            'slug' => 'teh',
            'sort_order' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('products')->insert([
            'category_id' => $categoryId,
            'prototype_id' => 'test-tea',
            'name' => 'Teh Melati',
            'description' => '',
            'price' => 12000,
            'options' => '[{"name":"Ukuran","options":[{"label":"Large","price":3000}]}]',
            'labels' => '[]',
            'available' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
                'product_id' => 'test-tea',
                'quantity' => 2,
                'options' => ['Ukuran' => 'Large'],
            ]],
        ])->assertCreated()->assertJson(['total' => 35000]);

        $this->assertDatabaseHas('orders', ['subtotal' => 30000, 'service_fee' => 5000, 'total' => 35000]);
    }

    public function test_admin_can_upload_and_remove_a_product_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/products', [
            'name' => 'Teh Foto',
            'category' => 'Teh',
            'price' => 12000,
            'options_json' => '[]',
            'image' => UploadedFile::fake()->image('teh.jpg'),
        ])->assertRedirect();

        $product = DB::table('products')->where('name', 'Teh Foto')->first();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonFragment(['image_url' => Storage::disk('public')->url($product->image_path)]);

        $this->put('/admin/products/'.$product->id, [
            'name' => 'Teh Foto',
            'category' => 'Teh',
            'price' => 12000,
            'options_json' => '[]',
            'remove_image' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'image_path' => null]);
        Storage::disk('public')->assertMissing($product->image_path);
    }
}
