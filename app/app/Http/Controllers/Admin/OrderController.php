<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Lets the store owner process incoming customer orders.
 */
class OrderController extends Controller
{
    /**
     * Lightweight polling payload for the dashboard.
     */
    public function index()
    {
        $orders = DB::table('orders');

        return response()->json([
            'count' => (clone $orders)->count(),
            'latest_id' => (clone $orders)->max('id'),
            'revenue' => (clone $orders)->where('status', '!=', OrderStatus::CANCELLED)->sum('total'),
        ]);
    }

    /**
     * Render the latest order rows so the dashboard can refresh in place.
     */
    public function table()
    {
        return response()->view('admin.partials.orders-rows', [
            'orders' => DB::table('orders')->latest()->get(),
        ]);
    }

    /**
     * Move an order to a new processing status.
     */
    public function update(Request $request, int $id)
    {
        abort_unless(DB::table('orders')->where('id', $id)->exists(), 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(OrderStatus::labels()))],
        ]);

        DB::table('orders')->where('id', $id)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        return response()->json([
            'id' => $id,
            'status' => $data['status'],
            'label' => OrderStatus::label($data['status']),
        ]);
    }
}
