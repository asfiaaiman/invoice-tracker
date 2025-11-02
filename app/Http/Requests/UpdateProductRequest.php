<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'agency_ids' => ['required', 'array', 'min:1'],
            'agency_ids.*' => ['exists:agencies,id'],
            'agency_prices' => ['nullable', 'array'],
            'agency_prices.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
