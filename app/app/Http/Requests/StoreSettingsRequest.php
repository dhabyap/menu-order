<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'whatsapp' => ['nullable', 'regex:/^62[1-9][0-9]{7,12}$/'],
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:10000000'],
            'delivery_fee_per_km' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'delivery_included_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'delivery_rounding_km' => ['nullable', 'numeric', 'gt:0', 'max:10'],
            'delivery_max_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'store_maps_url' => ['nullable', 'string', 'max:500'],
            'store_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'store_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'store_name' => ['required', 'string', 'max:120'],
            'store_tagline' => ['nullable', 'string', 'max:120'],
            'hero_heading' => ['required', 'string', 'max:160'],
            'hero_description' => ['required', 'string', 'max:500'],
            'marketing_badges' => ['nullable', 'string', 'max:1000'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', function (string $attribute, UploadedFile $value, callable $fail): void {
                $size = @getimagesize($value->getRealPath());
                if (! $size) {
                    $fail('Gambar tidak valid. Pastikan file gambar yang dipilih benar.');
                } elseif ($size[0] > 4000 || $size[1] > 4000) {
                    $fail('Dimensi logo terlalu besar. Maksimal 4000 × 4000 piksel.');
                }
            }],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizeTime = static function ($value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $value = trim($value);
            if ($value === '') {
                return null;
            }

            // Some clients submit HTML time values with seconds (HH:mm:ss).
            if (preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/', $value)) {
                return substr($value, 0, 5);
            }

            return $value;
        };

        $this->merge([
            'open_time' => $normalizeTime($this->input('open_time')),
            'close_time' => $normalizeTime($this->input('close_time')),
        ]);
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $open = $this->input('open_time');
                $close = $this->input('close_time');

                if ($open && $close && $open >= $close) {
                    $validator->errors()->add('open_time', 'Jam buka harus lebih awal dari jam tutup.');
                }

                $latitude = $this->input('store_latitude');
                $longitude = $this->input('store_longitude');
                if (($latitude === null || $latitude === '') !== ($longitude === null || $longitude === '')) {
                    $validator->errors()->add('store_latitude', 'Isi koordinat lintang dan bujur toko secara berpasangan.');
                }
                if ($latitude !== null && $latitude !== '' && (int) $this->input('delivery_fee_per_km', 0) <= 0) {
                    $validator->errors()->add('delivery_fee_per_km', 'Isi tarif per km lebih dari 0 untuk mengaktifkan ongkir berdasarkan jarak.');
                }
            },
        ];
    }
}
