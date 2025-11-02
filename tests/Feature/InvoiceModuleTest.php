<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;

test('guests cannot access invoices', function () {
    $response = $this->get('/invoices');
    $response->assertRedirect('/login');

    $invoice = Invoice::factory()->create();
    $response = $this->get("/invoices/{$invoice->id}");
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

test('authenticated users can filter invoices by agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $client1->agencies()->attach($agency1->id);
    $client2->agencies()->attach($agency2->id);

    Invoice::factory()->count(2)->create([
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
    ]);
    Invoice::factory()->count(3)->create([
        'agency_id' => $agency2->id,
        'client_id' => $client2->id,
    ]);

    $response = $this->get('/invoices?agency_id=' . $agency1->id);
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

test('authenticated users can filter invoices by date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => Carbon::now()->subDays(3),
    ]);
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => Carbon::now()->subDays(1),
    ]);
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => Carbon::now()->subDays(20),
    ]);

    $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/invoices?start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Invoices/Index')
             ->has('invoices.data', 2)
    );
});

test('authenticated users can view create invoice page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(2)->create(['is_active' => true]);

    $response = $this->get('/invoices/create');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Invoices/Create'));
});

test('authenticated users can create invoice with manual invoice number', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product = Product::factory()->create();
    $product->agencies()->attach($agency->id, ['price' => 100]);

    $data = [
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
                'description' => 'Test item',
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
    expect((float) $invoice->subtotal)->toBe(1000.0);
    expect((float) $invoice->tax_amount)->toBe(200.0);
    expect((float) $invoice->total)->toBe(1200.0);
});

test('invoice creation auto-generates per-agency invoice number when empty', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $client1->agencies()->attach($agency1->id);
    $client2->agencies()->attach($agency2->id);
    $product = Product::factory()->create();
    $product->agencies()->attach($agency1->id, ['price' => 100]);
    $product->agencies()->attach($agency2->id, ['price' => 100]);

    $agency1->update(['invoice_number_prefix' => 'INV']);
    
    Invoice::factory()->create([
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
        'invoice_number' => 'INV-' . now()->year . '-0001',
        'issue_date' => now(),
    ]);

    $data = [
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 100,
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $invoice = Invoice::where('agency_id', $agency1->id)
        ->whereYear('issue_date', now()->year)
        ->orderBy('invoice_number', 'desc')
        ->first();
    expect($invoice->invoice_number)->toBe('INV-' . now()->year . '-0002');
    expect($invoice->agency_id)->toBe($agency1->id);
});

test('invoice creation requires items', function () {
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

test('invoice can have multiple items with correct totals', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $product1->agencies()->attach($agency->id, ['price' => 100]);
    $product2->agencies()->attach($agency->id, ['price' => 200]);

    $data = [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 5,
                'unit_price' => 100,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 3,
                'unit_price' => 200,
            ],
        ],
    ];

    $response = $this->post('/invoices', $data);
    $response->assertSessionHas('success');

    $invoice = Invoice::where('invoice_number', 'INV-2025-0001')->first();
    expect($invoice->items)->toHaveCount(2);
    expect((float) $invoice->subtotal)->toBe(1100.0);
    expect((float) $invoice->tax_amount)->toBe(220.0);
    expect((float) $invoice->total)->toBe(1320.0);
});

test('authenticated users can view invoice show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create();

    $response = $this->get("/invoices/{$invoice->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Invoices/Show'));
});

test('authenticated users can update invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create();
    $product = Product::factory()->create();
    $product->agencies()->attach($invoice->agency_id, ['price' => 150]);

    $response = $this->put("/invoices/{$invoice->id}", [
        'agency_id' => $invoice->agency_id,
        'client_id' => $invoice->client_id,
        'invoice_number' => $invoice->invoice_number,
        'issue_date' => $invoice->issue_date->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 150,
            ],
        ],
    ]);

    $response->assertSessionHas('success');
    $invoice->refresh();
    expect($invoice->items)->toHaveCount(1);
});

test('authenticated users can soft delete invoice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create();

    $response = $this->delete("/invoices/{$invoice->id}");
    $response->assertRedirect('/invoices');
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
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

test('authenticated users can download invoice PDF', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->create();

    $response = $this->get("/invoices/{$invoice->id}/pdf");
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('invoice numbers are unique per agency per year', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $client1->agencies()->attach($agency1->id);
    $client2->agencies()->attach($agency2->id);
    $product = Product::factory()->create();
    $product->agencies()->attach($agency1->id, ['price' => 100]);
    $product->agencies()->attach($agency2->id, ['price' => 100]);

    $agency1->update(['invoice_number_prefix' => 'INV']);
    $agency2->update(['invoice_number_prefix' => 'INV']);
    
    Invoice::factory()->create([
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
        'invoice_number' => 'INV-' . now()->year . '-0001',
        'issue_date' => now(),
    ]);

    $data1 = [
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ];

    $data2 = [
        'agency_id' => $agency2->id,
        'client_id' => $client2->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ];

    $this->post('/invoices', $data1);
    $this->post('/invoices', $data2);

    $invoice1 = Invoice::where('agency_id', $agency1->id)
        ->whereYear('issue_date', now()->year)
        ->orderBy('invoice_number', 'desc')
        ->first();
    $invoice2 = Invoice::where('agency_id', $agency2->id)
        ->whereYear('issue_date', now()->year)
        ->orderBy('invoice_number', 'desc')
        ->first();

    expect($invoice1->invoice_number)->toBe('INV-' . now()->year . '-0002');
    expect($invoice2->invoice_number)->toBe('INV-' . now()->year . '-0001');
});

