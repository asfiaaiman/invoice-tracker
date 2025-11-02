<?php

use App\Actions\Invoice\GenerateInvoiceNumberAction;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates invoice number with prefix and year', function () {
    $agency = Agency::factory()->create();
    $action = new GenerateInvoiceNumberAction();

    $number = $action->execute($agency->id);

    expect($number)->toMatch('/^INV-\d{4}-\d{4}$/');
    expect($number)->toContain((string) now()->year);
});

test('increments invoice number for same agency and year', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2025-0001',
        'issue_date' => now(),
    ]);

    $action = new GenerateInvoiceNumberAction();
    $number = $action->execute($agency->id);

    expect($number)->toBe('INV-' . now()->year . '-0002');
});

test('resets invoice number for new year', function () {
    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2024-0010',
        'issue_date' => now()->subYear(),
    ]);

    $action = new GenerateInvoiceNumberAction();
    $number = $action->execute($agency->id);

    expect($number)->toBe('INV-' . now()->year . '-0001');
});

