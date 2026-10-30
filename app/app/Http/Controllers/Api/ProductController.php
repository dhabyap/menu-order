<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for the public product catalog.
 *
 * Handles:
 * - GET /api/products
 */
class ProductController extends Controller
{
    /**
     * List all products with their category, image, and racikan options.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function index()
    {
        return DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select('products.*', 'categories.name as category_name')
            ->orderBy('products.sort_order')
            ->get()
            ->map(function ($product) {
                $product->id = $product->prototype_id;
                $product->desc = $product->description;
                $product->image_url = $product->image_path
                    ? Storage::disk('public')->url($product->image_path)
                    : null;
                $product->groups = json_decode($product->options, true) ?: [];
                $product->labels = json_decode($product->labels, true) ?: [];

                return $product;
            });
    }
}
