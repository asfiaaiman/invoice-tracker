<?php

use App\Models\Agency;
use App\Models\Product;
use App\Models\User;

test('authenticated users can create product with multiple agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agencies = Agency::factory()->count(2)->create();

    $data = [
        'name' => 'Test Product',
        'description' => 'Test Description',
        'price' => 50000,
        'unit' => 'hour',
        'agency_ids' => $agencies->pluck('id')->toArray(),
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');
    $response->assertSessionHas('success');

    $product = Product::where('name', 'Test Product')->first();
    expect($product->agencies)->toHaveCount(2);
});

test('product requires at least one agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'price' => 50000,
        'agency_ids' => [],
    ]);

    $response->assertSessionHasErrors('agency_ids');
});

