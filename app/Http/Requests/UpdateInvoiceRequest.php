<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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
        $invoiceId = $this->route('invoice')->id ?? null;

        return [
            'agency_id' => ['required', 'exists:agencies,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')
                    ->ignore($invoiceId)
                    ->where('agency_id', $this->input('agency_id'))
                    ->whereNull('deleted_at'),
            ],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $index => $item) {
                if (empty($item['product_id']) && empty($item['description'])) {
                    $validator->errors()->add(
                        "items.{$index}.description",
                        'Description is required when no product is selected.'
                    );
                }
            }
        });
    }
}
