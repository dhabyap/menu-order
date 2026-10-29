<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * The endpoint is reachable by anonymous customers.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for a customer checkout.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^(?:\+?62|0)8[1-9][0-9]{7,11}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'delivery_quote_id' => ['nullable', 'string', 'max:5000'],
            'service' => ['required', 'in:Ambil Sendiri,Antar'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'string', 'exists:products,prototype_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.options' => ['nullable', 'array'],
        ];
    }
}
