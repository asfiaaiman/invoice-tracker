<?php

use App\Actions\Product\UpdateProductAction;
use App\Models\Agency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('update product action updates product basic data', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create([
        'name' => 'Old Name',
        'description' => 'Old Description',
        'price' => 40000,
    ]);
    
    $agency = Agency::factory()->create();
    $product->agencies()->attach($agency->id);
    
    $data = [
        'name' => 'New Name',
        'description' => 'New Description',
        'price' => 50000,
        'unit' => 'piece',
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency->id]);
    
    $updatedProduct->refresh();
    expect($updatedProduct->name)->toBe('New Name');
    expect($updatedProduct->description)->toBe('New Description');
    expect((float) $updatedProduct->price)->toBe(50000.0);
    expect($updatedProduct->unit)->toBe('piece');
});

test('update product action updates product code', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create(['code' => 'OLD-001']);
    $agency = Agency::factory()->create();
    $product->agencies()->attach($agency->id);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
        'code' => 'NEW-001',
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency->id]);
    
    $updatedProduct->refresh();
    expect($updatedProduct->code)->toBe('NEW-001');
});

test('update product action changes agency associations', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create();
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $agency3 = Agency::factory()->create();
    
    $product->agencies()->attach($agency1->id);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency2->id, $agency3->id]);
    
    $updatedProduct->load('agencies');
    expect($updatedProduct->agencies)->toHaveCount(2);
    expect($updatedProduct->agencies->pluck('id')->toArray())
        ->not->toContain($agency1->id);
    expect($updatedProduct->agencies->pluck('id')->toArray())
        ->toContain($agency2->id)
        ->toContain($agency3->id);
});

test('update product action updates agency-specific pricing', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create(['price' => 50000]);
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    
    $product->agencies()->attach($agency1->id, ['price' => 45000]);
    $product->agencies()->attach($agency2->id);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
    ];
    
    $agencyPrices = [
        $agency1->id => 55000,
        $agency2->id => 60000,
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency1->id, $agency2->id], $agencyPrices);
    
    $updatedProduct->load('agencies');
    $pivot1 = $updatedProduct->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $updatedProduct->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.0);
    expect((float) $pivot2->price)->toBe(60000.0);
});

test('update product action removes agency pricing when empty', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create();
    $agency = Agency::factory()->create();
    $product->agencies()->attach($agency->id, ['price' => 55000]);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
    ];
    
    $agencyPrices = [
        $agency->id => '',
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency->id], $agencyPrices);
    
    $updatedProduct->load('agencies');
    $pivot = $updatedProduct->agencies->first()->pivot;
    
    expect($pivot->price)->toBeNull();
});

test('update product action adds new agencies with pricing', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create();
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    
    $product->agencies()->attach($agency1->id);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
    ];
    
    $agencyPrices = [
        $agency1->id => 55000,
        $agency2->id => 60000,
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency1->id, $agency2->id], $agencyPrices);
    
    $updatedProduct->load('agencies');
    expect($updatedProduct->agencies)->toHaveCount(2);
    
    $pivot1 = $updatedProduct->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $updatedProduct->agencies->firstWhere('id', $agency2->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.0);
    expect((float) $pivot2->price)->toBe(60000.0);
});

test('update product action removes all agencies when empty array provided', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create();
    $agency = Agency::factory()->create();
    $product->agencies()->attach($agency->id);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
    ];
    
    $updatedProduct = $action->execute($product, $data, []);
    
    $updatedProduct->load('agencies');
    expect($updatedProduct->agencies)->toHaveCount(0);
});

test('update product action handles mixed pricing scenarios', function () {
    $action = new UpdateProductAction();
    
    $product = Product::factory()->create(['price' => 50000]);
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $agency3 = Agency::factory()->create();
    
    $product->agencies()->attach($agency1->id, ['price' => 45000]);
    $product->agencies()->attach($agency2->id);
    
    $data = [
        'name' => $product->name,
        'price' => $product->price,
    ];
    
    $agencyPrices = [
        $agency1->id => 55000,
        $agency3->id => 65000,
    ];
    
    $updatedProduct = $action->execute($product, $data, [$agency1->id, $agency2->id, $agency3->id], $agencyPrices);
    
    $updatedProduct->load('agencies');
    expect($updatedProduct->agencies)->toHaveCount(3);
    
    $pivot1 = $updatedProduct->agencies->firstWhere('id', $agency1->id)->pivot;
    $pivot2 = $updatedProduct->agencies->firstWhere('id', $agency2->id)->pivot;
    $pivot3 = $updatedProduct->agencies->firstWhere('id', $agency3->id)->pivot;
    
    expect((float) $pivot1->price)->toBe(55000.0);
    expect($pivot2->price)->toBeNull();
    expect((float) $pivot3->price)->toBe(65000.0);
});

