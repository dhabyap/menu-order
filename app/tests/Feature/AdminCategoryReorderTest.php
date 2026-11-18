<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCategoryReorderTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        return ['HTTP_REFERER' => url('/admin')];
    }

    public function test_guest_cannot_reorder(): void
    {
        $response = $this->patch('/admin/categories/reorder', ['order' => [1]]);

        $response->assertRedirect('/admin/login');
    }

    public function test_reorder_updates_sort_order(): void
    {
        $this->adminHeaders();

        Category::create(['name' => 'A', 'sort_order' => 1]);
        Category::create(['name' => 'B', 'sort_order' => 2]);
        Category::create(['name' => 'C', 'sort_order' => 3]);

        $ids = Category::orderBy('sort_order')->pluck('id')->all();

        $response = $this->patch('/admin/categories/reorder', ['order' => array_reverse($ids)], $this->adminHeaders());

        $response->assertSessionHas('success');

        $this->assertEquals(3, Category::find($ids[0])->sort_order);
        $this->assertEquals(2, Category::find($ids[1])->sort_order);
        $this->assertEquals(1, Category::find($ids[2])->sort_order);
    }

    public function test_reorder_rejects_invalid_ids(): void
    {
        $this->adminHeaders();

        $response = $this->patchJson('/admin/categories/reorder', ['order' => [99999]]);

        $response->assertJsonValidationErrors('order.0');
    }
}