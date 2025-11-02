<?php

use App\Actions\Invoice\GenerateInvoiceNumberAction;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates invoice number with default prefix and year', function () {
    $agency = Agency::factory()->create();
    $agency->update(['invoice_number_prefix' => 'INV']);
    $action = new GenerateInvoiceNumberAction();

    $number = $action->execute($agency->id);

    expect($number)->toMatch('/^INV-\d{4}-\d{4}$/');
    expect($number)->toContain((string) now()->year);
});

test('generates invoice number with custom agency prefix', function () {
    $agency = Agency::factory()->create(['invoice_number_prefix' => 'ABC']);
    $action = new GenerateInvoiceNumberAction();

    $number = $action->execute($agency->id);

    expect($number)->toMatch('/^ABC-\d{4}-\d{4}$/');
    expect($number)->toContain((string) now()->year);
    expect($number)->toStartWith('ABC-');
});

test('increments invoice number for same agency and year', function () {
    $agency = Agency::factory()->create(['invoice_number_prefix' => 'TST']);
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'TST-' . now()->year . '-0001',
        'issue_date' => now(),
    ]);

    $action = new GenerateInvoiceNumberAction();
    $number = $action->execute($agency->id);

    expect($number)->toBe('TST-' . now()->year . '-0002');
});

test('resets invoice number for new year', function () {
    $agency = Agency::factory()->create(['invoice_number_prefix' => 'XYZ']);
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'XYZ-2024-0010',
        'issue_date' => now()->subYear(),
    ]);

    $action = new GenerateInvoiceNumberAction();
    $number = $action->execute($agency->id);

    expect($number)->toBe('XYZ-' . now()->year . '-0001');
});

test('generates unique invoice numbers for different agencies with same prefix', function () {
    $agency1 = Agency::factory()->create(['invoice_number_prefix' => 'COM']);
    $agency2 = Agency::factory()->create(['invoice_number_prefix' => 'COM']);

    $action = new GenerateInvoiceNumberAction();
    $number1 = $action->execute($agency1->id);
    $number2 = $action->execute($agency2->id);

    expect($number1)->toBe($number2);
    expect($number1)->toMatch('/^COM-\d{4}-0001$/');
});

test('generates invoice numbers with different prefixes for different agencies', function () {
    $agency1 = Agency::factory()->create(['invoice_number_prefix' => 'AG1']);
    $agency2 = Agency::factory()->create(['invoice_number_prefix' => 'AG2']);

    $action = new GenerateInvoiceNumberAction();
    $number1 = $action->execute($agency1->id);
    $number2 = $action->execute($agency2->id);

    expect($number1)->toStartWith('AG1-');
    expect($number2)->toStartWith('AG2-');
});

test('invoice number generation includes soft deleted invoices in sequence', function () {
    $agency = Agency::factory()->create(['invoice_number_prefix' => 'TST']);
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'TST-' . now()->year . '-0001',
        'issue_date' => now(),
    ])->delete(); // Soft delete

    $action = new GenerateInvoiceNumberAction();
    $number = $action->execute($agency->id);

    expect($number)->toBe('TST-' . now()->year . '-0002'); // Should increment to 0002 since 0001 exists (soft deleted)
});

