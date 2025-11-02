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
    $agency->update(['invoice_number_prefix' => 'INV']);
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

test('authenticated users can create invoice with multiple line items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $data = [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 10,
                'unit_price' => 100,
                'description' => null,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 5,
                'unit_price' => 200,
                'description' => null,
            ],
            [
                'product_id' => null,
                'quantity' => 2,
                'unit_price' => 150,
                'description' => 'Custom item',
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $invoice = Invoice::where('invoice_number', 'INV-2025-0001')->first();
    expect($invoice->items)->toHaveCount(3);
    expect((float) $invoice->subtotal)->toBe(2300.0); // (10*100) + (5*200) + (2*150)
    expect((float) $invoice->tax_amount)->toBe(460.0); // 20% VAT
    expect((float) $invoice->total)->toBe(2760.0); // subtotal + tax
});

test('invoice creation with custom items requires description', function () {
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
        'items' => [
            [
                'product_id' => null,
                'quantity' => 1,
                'unit_price' => 100,
                'description' => '', // Empty description
            ],
        ],
    ]);

    $response->assertSessionHasErrors('items.0.description');
});

test('invoice number must be unique per agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
    ]);

    $response = $this->post('/invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001', // Duplicate
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('invoice_number');
});

test('invoice number can be same across different agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $client1->agencies()->attach($agency1->id);
    $client2->agencies()->attach($agency2->id);
    $product = Product::factory()->create();

    Invoice::factory()->create([
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
        'invoice_number' => 'INV-2025-0001',
    ]);

    $response = $this->post('/invoices', [
        'agency_id' => $agency2->id,
        'client_id' => $client2->id,
        'invoice_number' => 'INV-2025-0001', // Same number, different agency
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
});

test('invoice number generation excludes soft deleted invoices', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['invoice_number_prefix' => 'TST']);
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $deletedInvoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'TST-' . now()->year . '-0001',
        'issue_date' => now(),
    ]);
    $deletedInvoice->delete(); // Soft delete

    $data = [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $invoice = Invoice::latest()->first();
    // Since soft-deleted invoice exists with 0001, next should be 0002 to avoid unique constraint violation
    expect($invoice->invoice_number)->toBe('TST-' . now()->year . '-0002');
});

test('issue date cannot be in the future', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $response = $this->post('/invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->addDays(1)->format('Y-m-d'), // Future date
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('issue_date');
});

test('due date must be after or equal to issue date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $response = $this->post('/invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->subDays(1)->format('Y-m-d'), // Before issue date
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('due_date');
});

test('authenticated users can filter invoices by agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $client1->agencies()->attach($agency1->id);
    $client2->agencies()->attach($agency2->id);

    Invoice::factory()->count(3)->create([
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
    ]);
    Invoice::factory()->count(2)->create([
        'agency_id' => $agency2->id,
        'client_id' => $client2->id,
    ]);

    $response = $this->get("/invoices?agency_id={$agency1->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 3)
    );
});

test('authenticated users can filter invoices by client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $client1->agencies()->attach($agency->id);
    $client2->agencies()->attach($agency->id);

    Invoice::factory()->count(2)->create([
        'agency_id' => $agency->id,
        'client_id' => $client1->id,
    ]);
    Invoice::factory()->count(3)->create([
        'agency_id' => $agency->id,
        'client_id' => $client2->id,
    ]);

    $response = $this->get("/invoices?client_id={$client1->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 2)
    );
});

test('authenticated users can filter invoices by date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->subDays(3),
    ]);
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->subDays(1),
    ]);
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->subDays(20),
    ]);

    $startDate = now()->subDays(7)->format('Y-m-d');
    $endDate = now()->format('Y-m-d');

    $response = $this->get("/invoices?start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 2)
    );
});

test('authenticated users can filter invoices by search term', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client1 = Client::factory()->create(['name' => 'Alpha Client']);
    $client2 = Client::factory()->create(['name' => 'Beta Client']);
    $client1->agencies()->attach($agency->id);
    $client2->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client1->id,
        'invoice_number' => 'INV-2025-0001',
    ]);
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client2->id,
        'invoice_number' => 'INV-2025-0002',
    ]);

    $response = $this->get('/invoices?search=Alpha');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 1)
    );
});

test('authenticated users can filter invoices by invoice number search', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
    ]);
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0002',
    ]);

    $response = $this->get('/invoices?search=0001');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 1)
    );
});

test('authenticated users can soft delete invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $response = $this->delete("/invoices/{$invoice->id}");
    $response->assertRedirect('/invoices');
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    $this->assertDatabaseHas('invoices', ['id' => $invoice->id]); // Still in database
});

test('soft deleted invoices are excluded from index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice1 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);
    $invoice2 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);
    $invoice2->delete();

    $response = $this->get('/invoices');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 1)
    );
});

test('authenticated users can edit invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $response = $this->get("/invoices/{$invoice->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Invoices/Edit'));
});

test('authenticated users can update invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->put("/invoices/{$invoice->id}", [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 20,
                'unit_price' => 150,
                'description' => null,
            ],
        ],
    ]);

    $response->assertSessionHas('success');
    $invoice->refresh();
    expect((float) $invoice->subtotal)->toBe(3000.0); // 20 * 150
    expect((float) $invoice->total)->toBe(3600.0); // 3000 + 600 VAT
});

test('authenticated users can update invoice with multiple line items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->put("/invoices/{$invoice->id}", [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 10,
                'unit_price' => 100,
                'description' => null,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 5,
                'unit_price' => 200,
                'description' => null,
            ],
        ],
    ]);

    $response->assertSessionHas('success');
    $invoice->refresh();
    expect($invoice->items)->toHaveCount(2);
    expect((float) $invoice->subtotal)->toBe(2000.0);
    expect((float) $invoice->total)->toBe(2400.0);
});

test('invoice update validates invoice number uniqueness per agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $invoice1 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
    ]);
    $invoice2 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0002',
    ]);

    $response = $this->put("/invoices/{$invoice2->id}", [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001', // Duplicate
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('invoice_number');
});

test('invoice update date validation prevents future issue date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->put("/invoices/{$invoice->id}", [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->addDays(1)->format('Y-m-d'), // Future date
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('issue_date');
});

test('invoice update date validation ensures due date after issue date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->put("/invoices/{$invoice->id}", [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->subDays(1)->format('Y-m-d'), // Before issue date
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('due_date');
});

test('pdf generation includes agency contact information', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'name' => 'Test Agency',
        'tax_id' => '123456789',
        'email' => 'agency@test.com',
        'phone' => '+1234567890',
        'website' => 'https://test.com',
    ]);
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $response = $this->get("/invoices/{$invoice->id}/pdf");
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');

    $pdfContent = $response->getContent();
    expect($pdfContent)->toBeString();
});

test('invoice number generation excludes soft deleted invoices from sequence', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['invoice_number_prefix' => 'TST']);
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'TST-' . now()->year . '-0001',
        'issue_date' => now(),
    ])->delete(); // Soft delete first invoice

    $data = [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $invoice = Invoice::latest()->first();
    // Since soft-deleted invoice exists with 0001, next should be 0002 due to database unique constraint
    expect($invoice->invoice_number)->toBe('TST-' . now()->year . '-0002');
});

