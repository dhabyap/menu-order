<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StoreBrand;
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

        return [
            'delivery_fee' => (int) ($settings['delivery_fee'] ?? config('store.delivery_fee', 0)),
            'whatsapp' => $settings['whatsapp'] ?? config('store.whatsapp'),
            'store_name' => $settings['store_name'] ?? 'Racik Tea',
            'store_tagline' => $settings['store_tagline'] ?? '',
            'logo_url' => app(StoreBrand::class)->current()['logoUrl'],
            'hero_heading' => $settings['hero_heading'] ?? 'Pilih minuman favoritmu.',
            'hero_description' => $settings['hero_description'] ?? 'Telusuri menu yang tersedia dan sesuaikan racikan sebelum memesan.',
            'marketing_badges' => array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $settings['marketing_badges'] ?? '')))),
        ];
    }
}
