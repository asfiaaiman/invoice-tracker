<?php

use App\Actions\Report\GenerateAgencyReportAction;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates report with current year and last 365 days totals', function () {
    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 100000,
        'issue_date' => now()->subDays(10),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 200000,
        'issue_date' => now()->subMonths(2),
    ]);

    $action = new GenerateAgencyReportAction();
    $report = $action->execute($agency->id);

    expect($report)->toBeInstanceOf(\App\DTOs\ReportDTO::class);
    expect($report->last365DaysTotal)->toBeGreaterThan(0);
    expect($report->vatThreshold)->toBe(6000000.0);
});

test('validates minimum 5 clients rule', function () {
    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(4)->create(); // Only 4 clients
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->count(4)->create([
        'agency_id' => $agency->id,
        'client_id' => fn() => $clients->random()->id,
        'total' => 100000,
        'issue_date' => now()->subDays(10),
    ]);

    $action = new GenerateAgencyReportAction();
    $report = $action->execute($agency->id);

    $warnings = collect($report->warnings)->pluck('type')->toArray();
    expect($warnings)->toContain('min_clients');
});

test('validates maximum 70% client share rule', function () {
    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    // Client 1 has 75% of total (750000 out of 1000000)
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 750000,
        'issue_date' => now()->subDays(10),
    ]);

    Invoice::factory()->count(4)->create([
        'agency_id' => $agency->id,
        'client_id' => fn() => $clients->skip(1)->random()->id,
        'total' => 62500, // 250000 / 4 = 62500 each
        'issue_date' => now()->subDays(10),
    ]);

    $action = new GenerateAgencyReportAction();
    $report = $action->execute($agency->id);

    $warnings = collect($report->warnings)->pluck('type')->toArray();
    expect($warnings)->toContain('client_share');
});

test('calculates remaining amount until VAT threshold', function () {
    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 3000000, // 3 million RSD
        'issue_date' => now()->subDays(10), // Within last 365 days
    ]);

    $action = new GenerateAgencyReportAction();
    $report = $action->execute($agency->id);

    // remainingAmount is calculated from last365DaysTotal, not currentYearTotal
    expect($report->remainingAmount)->toBe(3000000.0); // 6000000 - 3000000
    expect($report->last365DaysTotal)->toBe(3000000.0);
});

