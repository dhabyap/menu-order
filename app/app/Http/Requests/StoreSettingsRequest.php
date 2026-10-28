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
            'store_name' => ['required', 'string', 'max:120'],
            'store_tagline' => ['nullable', 'string', 'max:120'],
            'hero_heading' => ['required', 'string', 'max:160'],
            'hero_description' => ['required', 'string', 'max:500'],
            'marketing_badges' => ['nullable', 'string', 'max:1000'],
            'open_time' => ['nullable', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
            'close_time' => ['nullable', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
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
        $this->merge([
            'open_time' => $this->string('open_time')->trim()->isEmpty() ? null : $this->string('open_time')->trim(),
            'close_time' => $this->string('close_time')->trim()->isEmpty() ? null : $this->string('close_time')->trim(),
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
            },
        ];
    }
}
