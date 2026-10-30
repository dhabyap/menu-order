<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreBrand
{
    public function current(): array
    {
        $settings = DB::table('store_settings')->pluck('value', 'key');
        $path = $settings['logo_path'] ?? null;

        return [
            'storeName' => $settings['store_name'] ?? 'Toko',
            'storeTagline' => $settings['store_tagline'] ?? '',
            'logoUrl' => $path && Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null,
        ];
    }
}
