<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminProductReorderTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        return ['HTTP_REFERER' => url('/admin')];
    }

    public function test_guest_cannot_reorder()
    {
        $response = $this->patch('/admin/products/reorder', ['order' => [1]]);

        $response->assertRedirect('/admin/login');
    }

    public function test_reorder_updates_sort_order()
    {
        $this->adminHeaders();

        DB::table('products')->insert([
            ['name' => 'A', 'price' => 1000, 'available' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'B', 'price' => 2000, 'available' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'C', 'price' => 3000, 'available' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $ids = DB::table('products')->orderBy('sort_order')->pluck('id')->all();

        $response = $this->patch('/admin/products/reorder', ['order' => array_reverse($ids)], $this->adminHeaders());

        $response->assertSessionHas('success');

        $this->assertEquals(3, DB::table('products')->where('id', $ids[0])->value('sort_order'));
        $this->assertEquals(2, DB::table('products')->where('id', $ids[1])->value('sort_order'));
        $this->assertEquals(1, DB::table('products')->where('id', $ids[2])->value('sort_order'));
    }

    public function test_reorder_rejects_invalid_ids()
    {
        $this->adminHeaders();

        $response = $this->patchJson('/admin/products/reorder', ['order' => [99999]]);

        $response->assertJsonValidationErrors('order.0');
    }
}
