<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_id' => ['required', 'exists:agencies,id'],
            'pdv_limit' => ['required', 'numeric', 'min:0'],
            'client_max_share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_clients_per_year' => ['required', 'integer', 'min:1'],
        ];
    }
}

