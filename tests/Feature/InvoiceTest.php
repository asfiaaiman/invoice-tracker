<?php

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\GenerateInvoiceNumberAction;
use App\DTOs\InvoiceDTO;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;

test('guests cannot access invoices', function () {
    $response = $this->get('/invoices');
    $response->assertRedirect('/login');
});

test('authenticated users can view invoices index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->count(3)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $response = $this->get('/invoices');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Invoices/Index'));
});

test('authenticated users can create invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->get('/invoices/create');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Invoices/Create'));
});

test('authenticated users can store invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $data = [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 100,
                'description' => null,
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
    ]);

    $invoice = Invoice::where('invoice_number', 'INV-2025-0001')->first();
    expect($invoice->items)->toHaveCount(1);
    expect((float) $invoice->total)->toBe(1200.0); // 1000 + 200 VAT
});

test('invoice creation auto-generates invoice number when empty', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $data = [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 100,
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $invoice = Invoice::latest()->first();
    expect($invoice->invoice_number)->not->toBeEmpty();
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

test('invoice validation requires items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $response = $this->post('/invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
});

test('authenticated users can download invoice PDF', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $response = $this->get("/invoices/{$invoice->id}/pdf");
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

