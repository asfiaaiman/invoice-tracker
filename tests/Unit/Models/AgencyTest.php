<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Product;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agency has many clients', function () {
    $agency = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();

    $agency->clients()->attach([$client1->id, $client2->id]);

    expect($agency->clients)->toHaveCount(2);
    expect($agency->clients->first()->id)->toBe($client1->id);
});

test('agency has many products', function () {
    $agency = Agency::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $agency->products()->attach($product1->id, ['price' => 1000]);
    $agency->products()->attach($product2->id, ['price' => 2000]);

    expect($agency->products)->toHaveCount(2);
    expect((float) $agency->products->first()->pivot->price)->toBe(1000.0);
});

test('agency has many invoices', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->count(3)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    expect($agency->invoices)->toHaveCount(3);
});

