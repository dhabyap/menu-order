<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manages the store settings shown in the customer catalog.
 */
class StoreSettingsController extends Controller
{
    /**
     * Store the editable store settings.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp' => ['nullable', 'regex:/^62[1-9][0-9]{7,12}$/'],
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'store_name' => ['required', 'string', 'max:120'],
            'store_tagline' => ['nullable', 'string', 'max:120'],
            'hero_heading' => ['required', 'string', 'max:160'],
            'hero_description' => ['required', 'string', 'max:500'],
            'marketing_badges' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ([
            'whatsapp' => $data['whatsapp'] ?? '',
            'delivery_fee' => (string) $data['delivery_fee'],
            'store_name' => $data['store_name'],
            'store_tagline' => $data['store_tagline'] ?? '',
            'hero_heading' => $data['hero_heading'],
            'hero_description' => $data['hero_description'],
            'marketing_badges' => $data['marketing_badges'] ?? '',
        ] as $key => $value) {
            DB::table('store_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('success', 'Pengaturan toko tersimpan.');
    }
}
