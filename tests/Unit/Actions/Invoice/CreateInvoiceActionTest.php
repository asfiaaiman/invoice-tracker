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

test('creates invoice with custom item without product', function () {
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
                'product_id' => null,
                'quantity' => 5,
                'unit_price' => 50,
                'description' => 'Custom service item',
            ],
        ],
    ]);

    $invoice = $action->execute($dto);

    expect($invoice)->toBeInstanceOf(\App\Models\Invoice::class);
    expect($invoice->items)->toHaveCount(1);
    
    $item = $invoice->items->first();
    expect($item->product_id)->toBeNull();
    expect($item->description)->toBe('Custom service item');
    expect((float) $item->quantity)->toBe(5.0);
    expect((float) $item->unit_price)->toBe(50.0);
    expect((float) $item->total)->toBe(250.0);
    expect((float) $invoice->subtotal)->toBe(250.0);
    expect((float) $invoice->tax_amount)->toBe(50.0);
    expect((float) $invoice->total)->toBe(300.0);
});

test('creates invoice with mixed product and custom items', function () {
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
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100,
                'description' => 'Product item',
            ],
            [
                'product_id' => null,
                'quantity' => 3,
                'unit_price' => 75,
                'description' => 'Custom item',
            ],
        ],
    ]);

    $invoice = $action->execute($dto);

    expect($invoice->items)->toHaveCount(2);
    
    $productItem = $invoice->items->firstWhere('product_id', $product->id);
    expect($productItem)->not->toBeNull();
    expect((float) $productItem->total)->toBe(200.0);
    
    $customItem = $invoice->items->firstWhere('product_id', null);
    expect($customItem)->not->toBeNull();
    expect($customItem->description)->toBe('Custom item');
    expect((float) $customItem->total)->toBe(225.0);
    
    expect((float) $invoice->subtotal)->toBe(425.0);
    expect((float) $invoice->tax_amount)->toBe(85.0);
    expect((float) $invoice->total)->toBe(510.0);
});

test('handles null product_id correctly in DTO', function () {
    $dto = InvoiceDTO::fromArray([
        'agency_id' => 1,
        'client_id' => 1,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => null,
                'quantity' => 1,
                'unit_price' => 100,
                'description' => 'Test',
            ],
        ],
    ]);

    $itemDTO = \App\DTOs\InvoiceItemDTO::fromArray($dto->items[0]);
    expect($itemDTO->productId)->toBeNull();
});

test('handles empty string product_id as null in DTO', function () {
    $dto = InvoiceDTO::fromArray([
        'agency_id' => 1,
        'client_id' => 1,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => '',
                'quantity' => 1,
                'unit_price' => 100,
                'description' => 'Test',
            ],
        ],
    ]);

    $itemDTO = \App\DTOs\InvoiceItemDTO::fromArray($dto->items[0]);
    expect($itemDTO->productId)->toBeNull();
});

