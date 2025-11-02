<?php

use App\Models\Agency;
use App\Models\Product;
use App\Models\User;

test('guests cannot access products', function () {
    $response = $this->get('/products');
    $response->assertRedirect('/login');

    $product = Product::factory()->create();
    $response = $this->get("/products/{$product->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view products index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Product::factory()->count(3)->create();

    $response = $this->get('/products');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Products/Index'));
});

test('authenticated users can view create product page with agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(2)->create(['is_active' => true]);
    Agency::factory()->create(['is_active' => false]);

    $response = $this->get('/products/create');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) =>
        $page->component('Products/Create')
             ->has('agencies', 2)
    );
});

test('authenticated users can create product with single agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Test Product',
        'description' => 'Product description',
        'price' => 50000.50,
        'unit' => 'hour',
        'agency_ids' => [$agency->id],
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'description' => 'Product description',
        'price' => 50000.50,
        'unit' => 'hour',
    ]);

    $product = Product::where('name', 'Test Product')->first();
    expect($product->agencies)->toHaveCount(1);
    expect($product->agencies->first()->id)->toBe($agency->id);
});

test('authenticated users can create product with multiple agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agencies = Agency::factory()->count(3)->create(['is_active' => true]);

    $data = [
        'name' => 'Multi-Agency Product',
        'price' => 75000,
        'unit' => 'piece',
        'agency_ids' => $agencies->pluck('id')->toArray(),
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');
    $response->assertSessionHas('success');

    $product = Product::where('name', 'Multi-Agency Product')->first();
    expect($product->agencies)->toHaveCount(3);
});

test('product creation requires at least one agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'price' => 50000,
        'agency_ids' => [],
    ]);

    $response->assertSessionHasErrors('agency_ids');
});

test('product creation requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/products', [
        'price' => 50000,
        'agency_ids' => [$agency->id],
    ]);

    $response->assertSessionHasErrors('name');
});

test('product creation requires price', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'agency_ids' => [$agency->id],
    ]);

    $response->assertSessionHasErrors('price');
});

test('product creation validates price is numeric and non-negative', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'price' => -100,
        'agency_ids' => [$agency->id],
    ]);

    $response->assertSessionHasErrors('price');
});

test('product creation validates agency exists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'price' => 50000,
        'agency_ids' => [99999],
    ]);

    $response->assertSessionHasErrors('agency_ids.0');
});

test('authenticated users can view product show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create();
    $agencies = Agency::factory()->count(2)->create();
    $product->agencies()->attach($agencies->pluck('id'));

    $response = $this->get("/products/{$product->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Products/Show'));
});

test('authenticated users can view product edit page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create();
    $agency = Agency::factory()->create(['is_active' => true]);
    $product->agencies()->attach($agency->id);

    $response = $this->get("/products/{$product->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Products/Edit'));
});

test('authenticated users can update product with changed agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create();
    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);
    $product->agencies()->attach($agency1->id, ['price' => 50000]);

    $response = $this->put("/products/{$product->id}", [
        'name' => 'Updated Product Name',
        'price' => $product->price,
        'unit' => $product->unit,
        'agency_ids' => [$agency2->id],
    ]);

    $response->assertRedirect('/products');
    $response->assertSessionHas('success');

    $product->refresh();
    expect($product->agencies)->toHaveCount(1);
    expect($product->agencies->first()->id)->toBe($agency2->id);
});

test('authenticated users can delete product', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create();

    $response = $this->delete("/products/{$product->id}");
    $response->assertRedirect('/products');
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

