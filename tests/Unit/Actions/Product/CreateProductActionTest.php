<?php

use App\Actions\Product\CreateProductAction;
use App\Models\Agency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create product action creates product with basic data', function () {
    $action = new CreateProductAction();
    
    $agency = Agency::factory()->create();
    
    $data = [
        'name' => 'Test Product',
        'description' => 'Test Description',
        'price' => 50000.50,
        'unit' => 'hour',
    ];
    
    $product = $action->execute($data, [$agency->id]);
    
    expect($product)->toBeInstanceOf(Product::class);
    expect($product->name)->toBe('Test Product');
    expect($product->description)->toBe('Test Description');
    expect((float) $product->price)->toBe(50000.50);
    expect($product->unit)->toBe('hour');
    expect($product->agencies)->toHaveCount(1);
});

test('create product action creates product with code', function () {
    $action = new CreateProductAction();
    
    $agency = Agency::factory()->create();
    
    $data = [
        'name' => 'Test Product',
        'code' => 'PROD-001',
        'price' => 50000,
        'unit' => 'piece',
    ];
    
    $product = $action->execute($data, [$agency->id]);
    
    expect($product->code)->toBe('PROD-001');
});

test('create product action associates product with multiple agencies', function () {
    $action = new CreateProductAction();
    
    $agencies = Agency::factory()->count(3)->create();
    
    $data = [
        'name' => 'Multi-Agency Product',
        'price' => 50000,
    ];
    
    $product = $action->execute($data, $agencies->pluck('id')->toArray());
    
    expect($product->agencies)->toHaveCount(3);
    expect($product->agencies->pluck('id')->toArray())
        ->toEqualCanonicalizing($agencies->pluck('id')->toArray());
});

test('create product action sets agency-specific pricing', function () {
    $action = new CreateProductAction();
    
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    
    $data = [
        'name' => 'Product With Pricing',
        'price' => 50000,
    ];
    
    $agencyPrices = [
        $agency1->id => 55000.50,
        $agency2->id => 60000,
    ];
    
    $product = $action->execute($data, [$agency1->id, $agency2->id], $agencyPrices);
    
    $product->load('agencies');
    $pivot1 = $product->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $product->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.50);
    expect((float) $pivot2->price)->toBe(60000.0);
});

test('create product action uses null price when agency price not provided', function () {
    $action = new CreateProductAction();
    
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    
    $data = [
        'name' => 'Product With Mixed Pricing',
        'price' => 50000,
    ];
    
    $agencyPrices = [
        $agency1->id => 55000,
    ];
    
    $product = $action->execute($data, [$agency1->id, $agency2->id], $agencyPrices);
    
    $product->load('agencies');
    $pivot1 = $product->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $product->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.0);
    expect($pivot2->price)->toBeNull();
});

test('create product action handles empty agency prices array', function () {
    $action = new CreateProductAction();
    
    $agency = Agency::factory()->create();
    
    $data = [
        'name' => 'Product Without Agency Pricing',
        'price' => 50000,
    ];
    
    $product = $action->execute($data, [$agency->id], []);
    
    $product->load('agencies');
    $pivot = $product->agencies->first()->pivot;
    
    expect($pivot->price)->toBeNull();
});

test('create product action handles string empty values in agency prices', function () {
    $action = new CreateProductAction();
    
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    
    $data = [
        'name' => 'Product With Empty Price',
        'price' => 50000,
    ];
    
    $agencyPrices = [
        $agency1->id => '',
        $agency2->id => null,
    ];
    
    $product = $action->execute($data, [$agency1->id, $agency2->id], $agencyPrices);
    
    $product->load('agencies');
    $pivot1 = $product->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $product->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect($pivot1->price)->toBeNull();
    expect($pivot2->price)->toBeNull();
});

test('create product action rolls back on error', function () {
    $action = new CreateProductAction();
    
    $agency = Agency::factory()->create();
    
    $data = [
        'name' => str_repeat('a', 300),
        'price' => 50000,
    ];
    
    try {
        $action->execute($data, [$agency->id]);
    } catch (\Exception $e) {
        // Expected to fail due to name length validation
    }
    
    $this->assertDatabaseMissing('products', ['name' => str_repeat('a', 300)]);
    $this->assertDatabaseMissing('agency_product', ['agency_id' => $agency->id]);
});

