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

test('authenticated users can create product with code field', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Product With Code',
        'code' => 'PROD-001',
        'price' => 50000,
        'unit' => 'piece',
        'agency_ids' => [$agency->id],
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');

    $this->assertDatabaseHas('products', [
        'name' => 'Product With Code',
        'code' => 'PROD-001',
    ]);
});

test('authenticated users can update product code field', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create(['code' => 'OLD-001']);
    $agency = Agency::factory()->create(['is_active' => true]);
    $product->agencies()->attach($agency->id);

    $response = $this->put("/products/{$product->id}", [
        'name' => $product->name,
        'code' => 'NEW-001',
        'price' => $product->price,
        'unit' => $product->unit,
        'agency_ids' => [$agency->id],
    ]);

    $response->assertRedirect('/products');
    $product->refresh();
    expect($product->code)->toBe('NEW-001');
});

test('product code field is optional', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Product Without Code',
        'price' => 50000,
        'agency_ids' => [$agency->id],
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');

    $product = Product::where('name', 'Product Without Code')->first();
    expect($product->code)->toBeNull();
});

test('authenticated users can create product with agency-specific pricing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Product With Agency Pricing',
        'price' => 50000,
        'unit' => 'hour',
        'agency_ids' => [$agency1->id, $agency2->id],
        'agency_prices' => [
            $agency1->id => 55000.50,
            $agency2->id => 60000,
        ],
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');
    $response->assertSessionHas('success');

    $product = Product::where('name', 'Product With Agency Pricing')->first();
    $product->load('agencies');
    
    expect($product->agencies)->toHaveCount(2);
    
    $pivot1 = $product->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $product->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.50);
    expect((float) $pivot2->price)->toBe(60000.0);
});

test('authenticated users can create product with some agencies using default price', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Product With Mixed Pricing',
        'price' => 50000,
        'unit' => 'hour',
        'agency_ids' => [$agency1->id, $agency2->id],
        'agency_prices' => [
            $agency1->id => 55000,
        ],
    ];

    $response = $this->post('/products', $data);
    $response->assertRedirect('/products');

    $product = Product::where('name', 'Product With Mixed Pricing')->first();
    $product->load('agencies');
    
    $pivot1 = $product->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $product->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.0);
    expect($pivot2->price)->toBeNull();
});

test('authenticated users can update product with agency-specific pricing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create(['price' => 50000]);
    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);
    $product->agencies()->attach($agency1->id, ['price' => 45000]);
    $product->agencies()->attach($agency2->id);

    $response = $this->put("/products/{$product->id}", [
        'name' => $product->name,
        'price' => 50000,
        'unit' => $product->unit,
        'agency_ids' => [$agency1->id, $agency2->id],
        'agency_prices' => [
            $agency1->id => 55000,
            $agency2->id => 60000,
        ],
    ]);

    $response->assertRedirect('/products');
    $product->refresh();
    $product->load('agencies');
    
    $pivot1 = $product->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $product->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.0);
    expect((float) $pivot2->price)->toBe(60000.0);
});

test('agency-specific pricing validation accepts numeric values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'price' => 50000,
        'agency_ids' => [$agency->id],
        'agency_prices' => [
            $agency->id => 'not-a-number',
        ],
    ]);

    $response->assertSessionHasErrors('agency_prices.' . $agency->id);
});

test('agency-specific pricing validation rejects negative values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/products', [
        'name' => 'Test Product',
        'price' => 50000,
        'agency_ids' => [$agency->id],
        'agency_prices' => [
            $agency->id => -100,
        ],
    ]);

    $response->assertSessionHasErrors('agency_prices.' . $agency->id);
});

test('product show page displays agency-specific pricing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create(['price' => 50000]);
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $product->agencies()->attach($agency1->id, ['price' => 55000]);
    $product->agencies()->attach($agency2->id);

    $response = $this->get("/products/{$product->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Products/Show')
             ->has('product.agencies', 2)
    );
});

test('product edit page includes agency pricing data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create();
    $agency = Agency::factory()->create(['is_active' => true]);
    $product->agencies()->attach($agency->id, ['price' => 55000]);

    $response = $this->get("/products/{$product->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Products/Edit')
             ->has('agencyPrices')
             ->has('agencyPrices.' . $agency->id)
    );
    
    $response->assertInertia(function ($page) use ($agency) {
        $price = $page->toArray()['props']['agencyPrices'][$agency->id];
        expect((float) $price)->toBe(55000.0);
    });
});

