<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Calculates and persists customer orders.
 *
 * All prices are resolved from the database so the client can never
 * dictate the amount that gets charged.
 */
class OrderService
{
    /**
     * Validation messages that the API contract exposes to customers.
     */
    public const MESSAGE_ADDRESS_REQUIRED = 'Alamat wajib diisi untuk layanan antar.';

    public const MESSAGE_PRODUCT_UNAVAILABLE = 'Menu tidak tersedia.';

    public const MESSAGE_STORE_CLOSED = 'Toko sedang tutup. Jam buka: %s - %s.';

    public const MESSAGE_OPTION_REQUIRED = 'Pilih satu opsi untuk setiap kelompok racikan.';

    public const MESSAGE_OPTION_INVALID = 'Opsi menu tidak valid.';

    public const MESSAGE_WHATSAPP_NOT_CONFIGURED = 'Pesanan belum disimpan karena nomor WhatsApp toko belum diatur. Silakan hubungi pengelola toko.';

    /**
     * Create an order from validated checkout data.
     *
     * @param  array<string, mixed>  $data
     * @return array{id: int, code: string, total: int, whatsapp: ?string}
     *
     * @throws RuntimeException when the order cannot be fulfilled.
     */
    public function create(array $data): array
    {
        $openTime = DB::table('store_settings')->where('key', 'open_time')->value('value');
        $closeTime = DB::table('store_settings')->where('key', 'close_time')->value('value');
        if ($openTime && $closeTime) {
            $now = now()->format('H:i');
            if ($now < $openTime || $now >= $closeTime) {
                throw new RuntimeException(sprintf(self::MESSAGE_STORE_CLOSED, $openTime, $closeTime));
            }
        }

        if ($data['service'] === 'Antar' && empty($data['address'])) {
            throw new RuntimeException(self::MESSAGE_ADDRESS_REQUIRED);
        }

        $this->ensureStoreIsOpen();

        $products = DB::table('products')
            ->whereIn('prototype_id', collect($data['items'])->pluck('product_id'))
            ->where('available', true)
            ->get()
            ->keyBy('prototype_id');

        if ($products->count() !== count($data['items'])) {
            throw new RuntimeException(self::MESSAGE_PRODUCT_UNAVAILABLE);
        }

        [$items, $subtotal] = $this->buildItems($data['items'], $products);

        $deliveryFee = DB::table('store_settings')->where('key', 'delivery_fee')->value('value');
        $whatsapp = trim((string) DB::table('store_settings')->where('key', 'whatsapp')->value('value'));
        if ($whatsapp === '') {
            $whatsapp = trim((string) config('store.whatsapp', ''));
        }
        if ($whatsapp === '') {
            throw new RuntimeException(self::MESSAGE_WHATSAPP_NOT_CONFIGURED);
        }

        $fee = $data['service'] === 'Antar' ? (int) ($deliveryFee ?? config('store.delivery_fee', 0)) : 0;
        $total = $subtotal + $fee;
        $code = 'RT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));

        $id = DB::table('orders')->insertGetId([
            'code' => $code,
            'customer_name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'service' => $data['service'],
            'note' => $data['note'] ?? null,
            'subtotal' => $subtotal,
            'service_fee' => $fee,
            'total' => $total,
            'status' => 'pending',
            'items' => json_encode($items),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'code' => $code,
            'total' => $total,
            'whatsapp' => $whatsapp,
        ];
    }

    /**
     * Resolve line items and their prices from the stored product data.
     *
     * @param  array<int, array<string, mixed>>  $requestedItems
     * @param  \Illuminate\Support\Collection<string, \stdClass>  $products
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     *
     * @throws RuntimeException when an option is missing or unknown.
     */
    private function buildItems(array $requestedItems, $products): array
    {
        $items = [];
        $subtotal = 0;

        foreach ($requestedItems as $row) {
            $product = $products[$row['product_id']];
            $availableOptions = $product->options ? json_decode($product->options, true) : [];
            $options = $row['options'] ?? [];
            $extra = 0;

            if (count($options) !== count($availableOptions)) {
                throw new RuntimeException(self::MESSAGE_OPTION_REQUIRED);
            }

            foreach ($options as $group => $choice) {
                $valid = collect($availableOptions)->firstWhere('name', $group);
                $option = $valid ? collect($valid['options'])->firstWhere('label', $choice) : null;

                if (! $option) {
                    throw new RuntimeException(self::MESSAGE_OPTION_INVALID);
                }

                $extra += (int) ($option['price'] ?? 0);
            }

            $unit = $product->price + $extra;
            $line = $unit * $row['quantity'];
            $subtotal += $line;

            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'options' => $options,
                'unit_price' => $unit,
                'quantity' => $row['quantity'],
                'subtotal' => $line,
            ];
        }

        return [$items, $subtotal];
    }

    private function ensureStoreIsOpen(): void
    {
        $open = trim((string) DB::table('store_settings')->where('key', 'open_time')->value('value'));
        $close = trim((string) DB::table('store_settings')->where('key', 'close_time')->value('value'));

        if ($open === '' || $close === '') {
            return;
        }

        if ($open === $close) {
            throw new RuntimeException(self::MESSAGE_STORE_CLOSED);
        }

        $now = now()->format('H:i');

        if ($open <= $close) {
            $closed = $now < $open || $now >= $close;
        } else {
            $closed = $now < $open && $now >= $close;
        }

        if ($closed) {
            throw new RuntimeException(self::MESSAGE_STORE_CLOSED);
        }
    }
}
