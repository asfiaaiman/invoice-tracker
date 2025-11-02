<?php

use App\Http\Requests\StoreAgencyRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('store agency request validates required fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new StoreAgencyRequest();
    $validator = validator(['name' => ''], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

test('store client request validates agency_ids', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new StoreClientRequest();
    $validator = validator(['name' => 'Test Client', 'agency_ids' => []], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('agency_ids'))->toBeTrue();
});

test('store invoice request validates items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $request = new StoreInvoiceRequest();
    $validator = validator([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('items'))->toBeTrue();
});

test('store invoice request validates item fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $request = new StoreInvoiceRequest();
    $validator = validator([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 0, // Invalid: must be > 0
                'unit_price' => 100,
            ],
        ],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('items.0.quantity'))->toBeTrue();
});

