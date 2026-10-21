<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Services\ProductImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Manages the product catalog from the admin dashboard.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductImageService $images) {}

    /**
     * Store a new menu item.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $categoryId = $this->syncCategory($data['category']);
        $imagePath = $request->hasFile('image') ? $this->images->store($request->file('image')) : null;

        DB::table('products')->insert([
            'category_id' => $categoryId,
            'prototype_id' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'image_path' => $imagePath,
            'labels' => json_encode($this->parseLabels($data['labels'] ?? '')),
            'options' => ($data['options_json'] ?? '[]') ?: '[]',
            'available' => true,
            'sort_order' => DB::table('products')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Menu baru berhasil ditambahkan.');
    }

    /**
     * Update an existing menu item.
     */
    public function update(StoreProductRequest $request, int $id)
    {
        $data = $request->validated();
        $product = DB::table('products')->where('id', $id)->first();
        abort_unless($product, 404);

        $categoryId = $this->syncCategory($data['category']);
        $imagePath = $product->image_path;

        if ($request->boolean('remove_image')) {
            $imagePath = null;
        }

        if ($request->hasFile('image')) {
            $imagePath = $this->images->store($request->file('image'));
        }

        DB::table('products')->where('id', $id)->update([
            'category_id' => $categoryId,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'image_path' => $imagePath,
            'labels' => json_encode($this->parseLabels($data['labels'] ?? '')),
            'options' => ($data['options_json'] ?? '[]') ?: '[]',
            'updated_at' => now(),
        ]);

        if ($product->image_path && $product->image_path !== $imagePath) {
            $this->images->delete($product->image_path);
        }

        return back()->with('success', 'Perubahan menu berhasil disimpan.');
    }

    /**
     * Toggle the availability flag of a menu item.
     */
    public function toggle(int $id)
    {
        $available = DB::table('products')->where('id', $id)->value('available');

        DB::table('products')->where('id', $id)->update([
            'available' => ! $available,
            'updated_at' => now(),
        ]);

        return back();
    }

    /**
     * Delete a menu item and its stored image.
     */
    public function destroy(int $id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        abort_unless($product, 404);

        DB::table('products')->where('id', $id)->delete();
        $this->images->delete($product->image_path);

        return back();
    }

    /**
     * Create or update the category row matching the given name.
     */
    private function syncCategory(string $name): int
    {
        $slug = Str::slug($name);

        DB::table('categories')->updateOrInsert(
            ['slug' => $slug],
            ['name' => $name, 'active' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        return (int) DB::table('categories')->where('slug', $slug)->value('id');
    }

    /**
     * Turn a comma separated label string into a clean list.
     *
     * @return array<int, string>
     */
    private function parseLabels(string $labels): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $labels))));
    }
}
