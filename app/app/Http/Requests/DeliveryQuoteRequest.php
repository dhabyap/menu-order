<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['address' => ['required', 'string', 'min:8', 'max:1000'], 'selected_lat' => ['nullable', 'numeric', 'between:-90,90'], 'selected_lon' => ['nullable', 'numeric', 'between:-180,180'], 'fallback' => ['nullable', 'boolean']];
    }
}
