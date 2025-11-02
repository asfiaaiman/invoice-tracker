<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;

test('invoice prefix from settings is used in invoice number generation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'invoice_number_prefix' => 'CUSTOM',
    ]);

    $client = Client::factory()->create();
    $agency->clients()->attach($client->id);

    // Update settings via agency settings endpoint
    $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'CUSTOM',
    ]);

    // Create invoice - should use CUSTOM prefix
    $product = \App\Models\Product::factory()->create();
    $agency->products()->attach($product->id, ['price' => 1000]);

    $response = $this->post('/invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    $invoice = Invoice::latest()->first();
    expect($invoice->invoice_number)->toStartWith('CUSTOM-');
});

test('application settings update affects invoice generation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'invoice_number_prefix' => 'INV',
    ]);

    $client = Client::factory()->create();
    $agency->clients()->attach($client->id);

    // Update via application settings
    $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'APP',
    ]);

    // Create invoice - should use APP prefix
    $product = \App\Models\Product::factory()->create();
    $agency->products()->attach($product->id, ['price' => 1000]);

    $response = $this->post('/invoices', [
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    $invoice = Invoice::latest()->first();
    expect($invoice->invoice_number)->toStartWith('APP-');
});

test('settings changes are reflected in report calculations', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);
    $client = Client::factory()->create();
    $agency->clients()->attach($client->id);

    // Create invoices
    Invoice::factory()->count(5)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 1000000,
        'issue_date' => now()->subDays(100),
    ]);

    // Update VAT threshold setting
    $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '10000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'TEST',
    ]);

    // Get report
    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $report = $page->toArray()['props']['report'];
        // Should use new VAT threshold of 10,000,000
        expect((float) $report['vat_threshold'])->toBe(10000000.0);
    });
});

test('multiple agencies can have different invoice prefixes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);

    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    $agency1->clients()->attach($client1->id);
    $agency2->clients()->attach($client2->id);

    // Set different prefixes for each agency
    $this->post("/agencies/{$agency1->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'AG1',
    ]);

    $this->post("/agencies/{$agency2->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'AG2',
    ]);

    // Create invoices for each agency
    $product1 = \App\Models\Product::factory()->create();
    $product2 = \App\Models\Product::factory()->create();
    $agency1->products()->attach($product1->id, ['price' => 1000]);
    $agency2->products()->attach($product2->id, ['price' => 1000]);

    $this->post('/invoices', [
        'agency_id' => $agency1->id,
        'client_id' => $client1->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [['product_id' => $product1->id, 'quantity' => 1, 'unit_price' => 1000]],
    ]);

    $this->post('/invoices', [
        'agency_id' => $agency2->id,
        'client_id' => $client2->id,
        'issue_date' => now()->format('Y-m-d'),
        'items' => [['product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 1000]],
    ]);

    $invoice1 = Invoice::where('agency_id', $agency1->id)->latest()->first();
    $invoice2 = Invoice::where('agency_id', $agency2->id)->latest()->first();

    expect($invoice1->invoice_number)->toStartWith('AG1-');
    expect($invoice2->invoice_number)->toStartWith('AG2-');
});

test('settings persistence across multiple updates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    // First update
    $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
        'invoice_number_prefix' => 'FIRST',
    ]);

    // Verify first update
    expect(Setting::where('agency_id', $agency->id)->where('key', 'pdv_limit')->first()->value)->toBe('7000000');
    expect($agency->fresh()->invoice_number_prefix)->toBe('FIRST');

    // Second update
    $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '8000000',
        'client_max_share_percent' => '80',
        'min_clients_per_year' => '7',
        'invoice_number_prefix' => 'SECOND',
    ]);

    // Verify second update overwrites first
    expect(Setting::where('agency_id', $agency->id)->where('key', 'pdv_limit')->first()->value)->toBe('8000000');
    expect(Setting::where('agency_id', $agency->id)->where('key', 'client_max_share_percent')->first()->value)->toBe('80');
    expect($agency->fresh()->invoice_number_prefix)->toBe('SECOND');
});

test('application settings and agency settings are synchronized', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    // Update via application settings
    $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '7500000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
        'invoice_number_prefix' => 'APP',
    ]);

    // View via agency settings - should show same values
    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertInertia(function ($page) {
        $settings = $page->toArray()['props']['settings'];
        expect($settings['pdv_limit'])->toBe('7500000');
        expect($settings['client_max_share_percent'])->toBe('75');
        expect($settings['invoice_number_prefix'])->toBe('APP');
    });
});

