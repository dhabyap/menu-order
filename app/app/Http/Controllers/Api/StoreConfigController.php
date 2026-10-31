<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StoreBrand;
use App\Support\StoreHours;
use Illuminate\Support\Facades\DB;

/**
 * Controller for managing store configuration.
 *
 * Handles:
 * - GET /api/store-config
 */
class StoreConfigController extends Controller
{
    /**
     * Return store configuration used by the customer catalog.
     *
     * @return array<string, mixed>
     */
    public function index()
    {
        $settings = DB::table('store_settings')->pluck('value', 'key');
        $whatsapp = trim((string) ($settings['whatsapp'] ?? ''));
        if ($whatsapp === '') {
            $whatsapp = trim((string) config('store.whatsapp', ''));
        }

        $hours = StoreHours::fromSettings($settings->all());
        $schedule = $hours->toArray();
        // Keep the documented display defaults when the owner has not set hours,
        // while still accepting orders at any time (is_open = true).
        $schedule['open_time'] = $hours->openTime() ?? config('store.open_time', '08:00');
        $schedule['close_time'] = $hours->closeTime() ?? config('store.close_time', '21:00');

        return array_merge([
            'delivery_fee' => (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)),
            'delivery_fee_per_km' => (int) ($settings['delivery_fee_per_km'] ?? 0),
            'delivery_included_km' => (float) ($settings['delivery_included_km'] ?? 0),
            'delivery_rounding_km' => (float) ($settings['delivery_rounding_km'] ?? 0.5),
            'delivery_max_km' => (float) ($settings['delivery_max_km'] ?? 0),
            'delivery_enabled' => is_numeric($settings['store_latitude'] ?? null) && is_numeric($settings['store_longitude'] ?? null),
            'whatsapp' => $whatsapp,
            'store_name' => $settings['store_name'] ?? 'Toko',
            'store_tagline' => $settings['store_tagline'] ?? '',
            'logo_url' => app(StoreBrand::class)->current()['logoUrl'],
            'hero_heading' => $settings['hero_heading'] ?? 'Pilih minuman favoritmu.',
            'hero_description' => $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.',
            'marketing_badges' => array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $settings['marketing_badges'] ?? '')))),
            'store_address' => $settings['store_address'] ?? '',
        ], $schedule);
    }
}
