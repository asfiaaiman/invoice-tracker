<?php

use App\Actions\Invoice\CalculateInvoiceTotalsAction;
use App\Actions\Invoice\CreateInvoiceAction;
use App\DTOs\InvoiceDTO;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates invoice with items', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $action = new CreateInvoiceAction(new CalculateInvoiceTotalsAction());

    $dto = InvoiceDTO::fromArray([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 100,
                'description' => null,
            ],
        ],
    ]);

    $invoice = $action->execute($dto);

    expect($invoice)->toBeInstanceOf(\App\Models\Invoice::class);
    expect($invoice->agency_id)->toBe($agency->id);
    expect($invoice->client_id)->toBe($client->id);
    expect($invoice->items)->toHaveCount(1);
    expect((float) $invoice->total)->toBe(1200.0); // 1000 + 200 (20% VAT)
});

test('creates invoice within transaction', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $action = new CreateInvoiceAction(new CalculateInvoiceTotalsAction());

    $dto = InvoiceDTO::fromArray([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => 999, // Non-existent product
                'quantity' => 10,
                'unit_price' => 100,
            ],
        ],
    ]);

    expect(fn() => $action->execute($dto))->toThrow(\Illuminate\Database\QueryException::class);

    expect(\App\Models\Invoice::count())->toBe(0);
});

