<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Renders the admin dashboard with catalog, orders, and store summary.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $products = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select('products.*', 'categories.name as category')
            ->orderBy('products.sort_order')
            ->get();
        $orders = DB::table('orders')->latest()->get();
        $settings = DB::table('store_settings')->pluck('value', 'key');

        return view('admin', [
            'products' => $products,
            'orders' => $orders,
            'available' => $products->where('available', 1)->count(),
            'unavailable' => $products->where('available', 0)->count(),
            'revenue' => $orders->where('status', '!=', 'cancelled')->sum('total'),
            'settings' => $settings,
        ]);
    }
}
