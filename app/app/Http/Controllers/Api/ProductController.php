<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\OrderStatus;
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
     * Number of calendar days (including today) used for best-seller ranking.
     */
    private const BEST_SELLER_DAYS = 30;

    /**
     * List all products with their category, image, and racikan options.
     *
     * Adds recommendation flag and best-seller metadata derived from stored
     * order line quantities inside the ranking period.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function index()
    {
        $products = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select('products.*', 'categories.name as category_name')
            ->orderBy('products.sort_order')
            ->orderBy('products.id')
            ->get();

        $sales = $this->salesByProductId();
        $ranks = $this->bestSellerRanks($products, $sales);

        return $products->map(function ($product) use ($sales, $ranks) {
            $productId = (int) $product->id;

            $product->id = $product->prototype_id;
            $product->desc = $product->description;
            $product->image_url = $product->image_path
                ? Storage::disk('public')->url($product->image_path)
                : null;
            $product->groups = json_decode($product->options, true) ?: [];
            $product->labels = json_decode($product->labels, true) ?: [];
            $product->is_recommended = (bool) $product->is_recommended;
            $product->view_count = (int) ($product->view_count ?? 0);
            $product->sold_quantity = $sales[$productId] ?? 0;
            $product->best_seller_rank = $ranks[$productId] ?? null;
            $product->is_best_seller = isset($ranks[$productId]);

            return $product;
        });
    }

    /**
     * Sum ordered quantities per database product id within the ranking period.
     *
     * @return array<int, int>
     */
    private function salesByProductId(): array
    {
        $since = now()->subDays(self::BEST_SELLER_DAYS - 1)->startOfDay();

        $rows = DB::table('orders')
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->where('created_at', '>=', $since)
            ->pluck('items');

        $sales = [];

        foreach ($rows as $rawItems) {
            $items = is_string($rawItems) ? json_decode($rawItems, true) : $rawItems;

            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);

                if ($productId === false || $quantity === false || $productId <= 0 || $quantity <= 0) {
                    continue;
                }

                $sales[$productId] = ($sales[$productId] ?? 0) + $quantity;
            }
        }

        return $sales;
    }

    /**
     * Build a deterministic rank map for available products that have sales.
     *
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $products
     * @param  array<int, int>  $sales
     * @return array<int, int>
     */
    private function bestSellerRanks($products, array $sales): array
    {
        $ranked = $products
            ->filter(fn ($product) => $product->available && ($sales[(int) $product->id] ?? 0) > 0)
            ->sortBy([
                fn ($a, $b) => $sales[(int) $b->id] <=> $sales[(int) $a->id],
                fn ($a, $b) => (int) $a->sort_order <=> (int) $b->sort_order,
                fn ($a, $b) => (int) $a->id <=> (int) $b->id,
            ])
            ->values();

        $ranks = [];

        foreach ($ranked as $index => $product) {
            $ranks[(int) $product->id] = $index + 1;
        }

        return $ranks;
    }
}