<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice belongs to agency', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    expect($invoice->agency->id)->toBe($agency->id);
});

test('invoice belongs to client', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    expect($invoice->client->id)->toBe($client->id);
});

test('invoice has many items', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    InvoiceItem::factory()->count(3)->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
    ]);

    expect($invoice->items)->toHaveCount(3);
});

test('invoice can be soft deleted', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $invoiceId = $invoice->id;
    $invoice->delete();

    expect(Invoice::find($invoiceId))->toBeNull();
    expect(Invoice::withTrashed()->find($invoiceId))->not->toBeNull();
});

