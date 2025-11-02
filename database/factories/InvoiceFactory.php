<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'client_id' => Client::factory(),
            'invoice_number' => 'INV-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'issue_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'due_date' => fn(array $attributes) => fake()->dateTimeBetween($attributes['issue_date'], '+30 days'),
            'subtotal' => fake()->randomFloat(2, 10000, 1000000),
            'tax_amount' => fn(array $attributes) => $attributes['subtotal'] * 0.20,
            'total' => fn(array $attributes) => $attributes['subtotal'] + $attributes['tax_amount'],
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
