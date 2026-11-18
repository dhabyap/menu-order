<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Manages product categories for the admin dashboard.
 * Supports list with drag-drop sort, create, edit, delete, and merge.
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::ordered()->get();

        return view('admin.partials.categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['active'] = $data['active'] ?? true;

        $category = Category::create($data);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sort_order' => $category->sort_order,
            'active' => $category->active,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sort_order' => $category->sort_order,
            'active' => $category->active,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * Prevents deletion if category has products unless they are moved first.
     */
    public function destroy(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $productsCount = Product::where('category_id', $id)->count();

        if ($productsCount > 0) {
            return response()->json([
                'message' => "Kategori memiliki {$productsCount} produk. Pindahkan produk ke kategori lain sebelum menghapus, atau gunakan fitur gabung.",
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori dihapus.']);
    }

    /**
     * Merge multiple categories into one target category.
     * All products from source categories are moved to the target.
     * Source categories are deleted after merge.
     */
    public function merge(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:2'],
            'ids.*' => ['required', 'exists:categories,id'],
            'target_id' => ['required', 'exists:categories,id'],
        ]);

        $ids = $data['ids'];
        $targetId = $data['target_id'];

        // Ensure target is not in the source list
        if (in_array($targetId, $ids, true)) {
            return response()->json([
                'message' => 'Kategori target tidak boleh berada dalam daftar yang digabung.',
            ], 422);
        }

        DB::transaction(function () use ($ids, $targetId) {
            // Move all products to target category
            Product::whereIn('category_id', $ids)->update(['category_id' => $targetId]);

            // Delete source categories (excluding target)
            Category::whereIn('id', $ids)
                ->where('id', '!=', $targetId)
                ->delete();
        });

        return response()->json(['message' => 'Kategori berhasil digabung.']);
    }

    /**
     * Reorder categories via drag-drop.
     * Expects 'order' array of category IDs in the new order.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:categories,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $id) {
                Category::where('id', $id)->update(['sort_order' => $index + 1]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Urutan kategori diperbarui.']);
        }

        return back()->with('success', 'Urutan kategori disimpan.');
    }
}
