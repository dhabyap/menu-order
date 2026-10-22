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
            'store_name' => $data['store_name'],
            'store_tagline' => $data['store_tagline'] ?? '',
            'hero_heading' => $data['hero_heading'],
            'hero_description' => $data['hero_description'],
            'marketing_badges' => $data['marketing_badges'] ?? '',
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
