<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/store-config', function () {
    $settings = \Illuminate\Support\Facades\DB::table('store_settings')->pluck('value', 'key');

    return [
        'delivery_fee' => (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)),
        'whatsapp' => $settings['whatsapp'] ?? config('store.whatsapp'),
        'store_name' => $settings['store_name'] ?? 'Racik Tea',
        'store_tagline' => $settings['store_tagline'] ?? '',
        'hero_heading' => $settings['hero_heading'] ?? 'Pilih minuman favoritmu.',
        'hero_description' => $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.',
        'marketing_badges' => array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $settings['marketing_badges'] ?? '')))),
    ];
});
Route::get('/products', fn () => \Illuminate\Support\Facades\DB::table('products')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->select('products.*', 'categories.name as category_name')->orderBy('products.sort_order')->get()->map(function ($product) { $product->id = $z->prototype_id; $product->desc = $product->description; $product->image_url = $product->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) : null; $product->groups = json_decode($product->options, true) ?: []; $product->labels = json_decode($product->labels, true) ?: []; return $product; }));

Route::post('/orders', function (Request $request) {
    $data = $request->validate([
        'name' => ['required','string','max:120'], 'phone' => ['required','regex:/^(?:\+?62|0)8[1-9][0-9]{7,11}$/'],
        'address' => ['nullable','string','max:1000'], 'service' => ['required','in:Ambil Sendiri,Antar'],
        'note' => ['nullable','string','max:2000'], 'items' => ['required','array','min:1','max:50'],
        'items.*.product_id' => ['required','string','exists:products,prototype_id'], 'items.*.quantity' => ['required','integer','min:1','max:99'], 'items.*.options' => ['nullable','array'],
    ]);
    if ($data['service'] === 'Antar' && empty($data['address'])) return response()->json(['message' => 'Alamat wajib diisi untuk layanan antar.'], 422);
    $products = \Illuminate\Support\Facades\DB::table('products')->whereIn('prototype_id', collect($data['items'])->pluck('product_id'))->where('available', true)->get()->keyBy('prototype_id');
    if ($products->count() !== count($data['items'])) return response()->json(['message' => 'Menu tidak tersedia.'], 422);
    $items = []; $subtotal = 0;
    foreach ($data['items'] as $row) {
        $product = $products[$row['product_id']]; $availableOptions = $product->options ? json_decode($product->options, true) : [];
        $options = $row['options'] ?? []; $extra = 0;
        if (count($options) !== count($availableOptions)) return response()->json(['message' => 'Pilih satu opsi untuk setiap kelompok racikan.'], 422);
        foreach ($options as $group => $choice) {
            $valid = collect($availableOptions)->firstWhere('name', $group);
            $option = $valid ? collect($valid['options'])->firstWhere('label', $choice) : null;
            if (!$option) return response()->json(['message' => 'Opsi menu tidak valid.'], 422);
            $extra += (int) ($option['price'] ?? 0);
        }
        $unit = $product->price + $extra; $line = $unit * $row['quantity']; $subtotal += $line;
        $items[] = ['product_id' => $product->id, 'name' => $product->name, 'options' => $options, 'unit_price' => $unit, 'quantity' => $row['quantity'], 'subtotal' => $line];
    }
    $deliveryFee = \Illuminate\Support\Facades\DB::table('store_settings')->where('key', 'delivery_fee')->value('value');
    $whatsapp = \Illuminate\Support\Facades\DB::table('store_settings')->where('key', 'whatsapp')->value('value');
    $fee = $data['service'] === 'Antar' ? (int) ($deliveryFee ?? config('store.delivery_fee', 0)) : 0;
    $code = 'RT-' . now()->format('ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(5));
    $id = \Illuminate\Support\Facades\DB::table('orders')->insertGetId(['code' => $code, 'customer_name' => $data['name'], 'phone' => $data['phone'], 'address' => $data['address'] ?? null, 'service' => $data['service'], 'note' => $data['note'] ?? null, 'subtotal' => $subtotal, 'service_fee' => $fee, 'total' => $subtotal + $fee, 'status' => 'pending', 'items' => json_encode($items), 'created_at' => now(), 'updated_at' => now()]);
    return response()->json(['id' => $id, 'code' => $code, 'total' => $subtotal + $fee, 'whatsapp' => $whatsapp ?? config('store.whatsapp')], 201);
})->middleware('throttle:10,1');
