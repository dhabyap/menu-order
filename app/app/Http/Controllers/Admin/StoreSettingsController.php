<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSettingsRequest;
use App\Services\ProductImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreSettingsController extends Controller
{
    public function update(StoreSettingsRequest $request, ProductImageService $images)
    {
        $data = $request->validated();
        $oldLogo = DB::table('store_settings')->where('key', 'logo_path')->value('value');
        $newLogo = null;

        if ($request->hasFile('logo')) {
            $newLogo = $images->store($request->file('logo'));
        }

        foreach ([
            'whatsapp' => $data['whatsapp'] ?? '',
            'delivery_fee' => (string) $data['delivery_fee'],
            'delivery_fee_per_km' => (string) ($data['delivery_fee_per_km'] ?? 0),
            'delivery_included_km' => (string) ($data['delivery_included_km'] ?? 0),
            'delivery_rounding_km' => (string) ($data['delivery_rounding_km'] ?? 0.5),
            'delivery_max_km' => (string) ($data['delivery_max_km'] ?? 0),
            'store_address' => $data['store_address'] ?? '',
            'store_latitude' => $data['store_latitude'] ?? null,
            'store_longitude' => $data['store_longitude'] ?? null,
            'store_name' => $data['store_name'],
            'store_tagline' => $data['store_tagline'] ?? '',
            'hero_heading' => $data['hero_heading'],
            'hero_description' => $data['hero_description'],
            'marketing_badges' => $data['marketing_badges'] ?? '',
            'open_time' => $data['open_time'] ?? null,
            'close_time' => $data['close_time'] ?? null,
        ] as $key => $value) {
            DB::table('store_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
        }

        if ($newLogo) {
            DB::table('store_settings')->updateOrInsert(['key' => 'logo_path'], ['value' => $newLogo, 'updated_at' => now(), 'created_at' => now()]);
        } elseif ($request->boolean('remove_logo')) {
            DB::table('store_settings')->where('key', 'logo_path')->delete();
        }

        if (($newLogo || $request->boolean('remove_logo')) && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return back()->with('success', 'Pengaturan toko tersimpan.');
    }
}
