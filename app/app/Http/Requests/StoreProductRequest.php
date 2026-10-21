<?php

namespace App\Http\Requests;

use App\Services\ProductOptionsValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validation rules for the admin product form.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'options_json' => ['nullable', 'json', 'max:10000'],
            'labels' => ['nullable', 'string', 'max:200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Extend validation with the racikan option structure check.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! app(ProductOptionsValidator::class)->isValid($this->input('options_json'))) {
                $validator->errors()->add('options_json', 'Format opsi racikan tidak valid.');
            }
        });
    }
}
