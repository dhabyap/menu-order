<?php
$order = DB::table('orders')->orderByDesc('created_at')->first();
if (!$order) { echo "no orders\n"; return; }
echo json_encode($order, JSON_PRETTY_PRINT) . PHP_EOL;
echo "--- items ---\n";
foreach (DB::table('order_items')->where('order_id', $order->id)->get() as $item) {
    echo json_encode($item, JSON_PRETTY_PRINT) . PHP_EOL;
}
