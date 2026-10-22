<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupOrderRequest extends FormRequest
{
    /**
     * The endpoint is reachable by anonymous customers.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for looking up orders by phone number.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^(?:\+?62|0)8[1-9][0-9]{7,11}$/'],
        ];
    }
}
