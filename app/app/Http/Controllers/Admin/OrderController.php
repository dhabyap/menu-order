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
     * Full detail of one order for the dashboard dialog.
     */
    public function show(int $id)
    {
        $order = DB::table('orders')->find($id);

        abort_unless($order, 404);

        $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;

        return response()->json([
            'id' => (int) $order->id,
            'code' => $order->code,
            'customer_name' => $order->customer_name,
            'phone' => $order->phone,
            'address' => $order->address,
            'service' => $order->service,
            'note' => $order->note,
            'subtotal' => (int) $order->subtotal,
            'service_fee' => (int) $order->service_fee,
            'total' => (int) $order->total,
            'status' => $order->status,
            'status_label' => OrderStatus::label($order->status),
            'items' => is_array($items) ? $items : [],
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
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

    /**
     * Export all orders to a downloadable CSV file.
     */
    public function export()
    {
        $orders = DB::table('orders')->latest()->get();

        $filename = 'pesanan-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['Kode', 'Nama', 'Telepon', 'Alamat', 'Layanan', 'Total', 'Status', 'Tanggal']);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->code,
                    $order->customer_name,
                    $order->phone,
                    $order->address ?? '',
                    $order->service,
                    (int) $order->total,
                    OrderStatus::label($order->status),
                    $order->created_at,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
