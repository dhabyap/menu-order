<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LookupOrderRequest;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

/**
 * Lets a customer look up their own orders by phone number.
 *
 * No customer account exists, so the phone number is the identity key.
 * The response is limited to fields a customer may see about their own order.
 */
class OrderLookupController extends Controller
{
    /**
     * Maximum number of orders returned for a single lookup.
     */
    private const LIMIT = 50;

    /**
     * Return the orders that belong to the given phone number.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LookupOrderRequest $request)
    {
        $phone = $this->normalizePhone($request->validated()['phone']);

        $rows = DB::table('orders')
            ->whereIn('phone', $this->phoneVariants($phone))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get(['code', 'service', 'status', 'subtotal', 'service_fee', 'total', 'created_at', 'updated_at', 'items']);

        $orders = $rows->map(fn ($row) => [
            'code' => $row->code,
            'service' => $row->service,
            'status' => $row->status,
            'status_label' => OrderStatus::label($row->status),
            'subtotal' => (int) $row->subtotal,
            'service_fee' => (int) $row->service_fee,
            'total' => (int) $row->total,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'items' => $this->items($row->items),
        ])->values()->all();

        return Response::json(['orders' => $orders]);
    }

    /**
     * Normalize any accepted Indonesian phone format to the 08... form.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * All stored representations of one phone number.
     *
     * @return array<int, string>
     */
    private function phoneVariants(string $phone): array
    {
        return array_values(array_unique([$phone, '62'.substr($phone, 1)]));
    }

    /**
     * Keep only customer-safe line item fields.
     *
     * @return array<int, array<string, mixed>>
     */
    private function items(mixed $raw): array
    {
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];

        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $options = [];

            if (is_array($item['options'] ?? null)) {
                foreach ($item['options'] as $key => $value) {
                    if (is_scalar($value)) {
                        $options[(string) $key] = (string) $value;
                    }
                }
            }

            $items[] = [
                'name' => (string) ($item['name'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'options' => $options,
                'unit_price' => (int) ($item['unit_price'] ?? 0),
                'subtotal' => (int) ($item['subtotal'] ?? 0),
            ];
        }

        return $items;
    }
}
