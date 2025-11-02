<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Service',
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10000, 100000),
            'unit' => fake()->randomElement(['hour', 'piece', 'day', 'month']),
        ];
    }
}
