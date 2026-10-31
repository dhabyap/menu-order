<?php

namespace App\Services;

use App\Support\StoreHours;
use Illuminate\Database\QueryException;
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
    public function __construct(private readonly DeliveryQuoteService $deliveryQuotes) {}

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
        $clientToken = trim((string) ($data['client_token'] ?? ''));

        // Retrying the same checkout (double click, lost response, popup retry)
        // must return the order that was already saved instead of creating a duplicate.
        if ($clientToken !== '') {
            $existing = DB::table('orders')->where('client_token', $clientToken)->first();
            if ($existing) {
                return $this->resultFromOrder($existing);
            }
        }

        if ($data['service'] === 'Antar' && empty($data['address'])) {
            throw new RuntimeException(self::MESSAGE_ADDRESS_REQUIRED);
        }

        $this->ensureStoreIsOpen();
        $deliveryQuote = null;
        if ($data['service'] === 'Antar') {
            $hasConfiguredOrigin = DB::table('store_settings')->where('key', 'store_latitude')->value('value') !== null
                && DB::table('store_settings')->where('key', 'store_longitude')->value('value') !== null;
            if ($hasConfiguredOrigin) {
                if (empty($data['delivery_quote_id'])) {
                    throw new RuntimeException('Hitung dan konfirmasi ongkir sebelum membuat pesanan.');
                }
                $deliveryQuote = $this->deliveryQuotes->validateQuote($data['delivery_quote_id'], $data['address']);
            }
        }

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
        $whatsapp = $this->resolveWhatsApp();
        if ($whatsapp === '') {
            throw new RuntimeException(self::MESSAGE_WHATSAPP_NOT_CONFIGURED);
        }

        $fee = $data['service'] === 'Antar'
            ? (int) ($deliveryQuote['fee'] ?? $deliveryFee ?? config('store.delivery_fee', 0))
            : 0;
        $baseFeeSnapshot = $deliveryQuote['base_fee'] ?? ($data['service'] === 'Antar' ? $fee : 0);
        $variableFeeSnapshot = $deliveryQuote ? max(0, $fee - (int) $baseFeeSnapshot) : 0;
        $total = $subtotal + $fee;
        $code = 'RT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));

        try {
            $id = DB::table('orders')->insertGetId([
                'code' => $code,
                'client_token' => $clientToken !== '' ? $clientToken : null,
                'customer_name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'service' => $data['service'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'service_fee' => $fee,
                'delivery_distance_meters' => $deliveryQuote['distance_meters'] ?? null,
                'delivery_base_fee' => $baseFeeSnapshot,
                'delivery_variable_fee' => $variableFeeSnapshot,
                'total' => $total,
                'status' => 'pending',
                'items' => json_encode($items),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // A concurrent request may have inserted the same token first.
            if ($clientToken !== '') {
                $existing = DB::table('orders')->where('client_token', $clientToken)->first();
                if ($existing) {
                    return $this->resultFromOrder($existing);
                }
            }

            throw $e;
        }

        return [
            'id' => $id,
            'code' => $code,
            'customer_name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'service' => $data['service'],
            'note' => $data['note'] ?? null,
            'subtotal' => $subtotal,
            'service_fee' => $fee,
            'total' => $total,
            'delivery_distance_meters' => $deliveryQuote['distance_meters'] ?? null,
            'items' => $items,
            'whatsapp' => $whatsapp,
        ];
    }

    /**
     * Rebuild the checkout response for an order that already exists.
     *
     * @return array<string, mixed>
     */
    private function resultFromOrder(object $order): array
    {
        $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;

        return [
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
            'delivery_distance_meters' => $order->delivery_distance_meters ?? null,
            'items' => is_array($items) ? $items : [],
            'whatsapp' => $this->resolveWhatsApp(),
        ];
    }

    private function resolveWhatsApp(): string
    {
        $whatsapp = trim((string) DB::table('store_settings')->where('key', 'whatsapp')->value('value'));

        return $whatsapp !== '' ? $whatsapp : trim((string) config('store.whatsapp', ''));
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

            $note = trim((string) ($row['note'] ?? ''));

            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'options' => $options,
                'note' => $note !== '' ? $note : null,
                'unit_price' => $unit,
                'quantity' => $row['quantity'],
                'subtotal' => $line,
            ];
        }

        return [$items, $subtotal];
    }

    private function ensureStoreIsOpen(): void
    {
        $settings = DB::table('store_settings')
            ->whereIn('key', ['open_time', 'close_time'])
            ->pluck('value', 'key')
            ->all();

        $hours = StoreHours::fromSettings($settings);

        if (! $hours->isOpen()) {
            throw new RuntimeException(sprintf(
                self::MESSAGE_STORE_CLOSED,
                $hours->openTime(),
                $hours->closeTime(),
            ));
        }
    }
}
