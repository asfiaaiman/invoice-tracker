<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

test('guests cannot access reports', function () {
    $response = $this->get('/reports');
    $response->assertRedirect('/login');

    $agency = Agency::factory()->create();
    $response = $this->get("/reports/{$agency->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view reports index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(3)->create(['is_active' => true]);
    Agency::factory()->create(['is_active' => false]);

    $response = $this->get('/reports');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Reports/Index'));
});

test('authenticated users can view agency report', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Reports/Show'));
});

test('report shows current year total from jan 1 to today', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    $currentYearStart = Carbon::now()->startOfYear();

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 500000,
        'issue_date' => $currentYearStart->copy()->addDays(10),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 300000,
        'issue_date' => $currentYearStart->copy()->addDays(20),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(2)->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subYear()->startOfYear(),
    ]);

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $currentYearTotal = $page->toArray()['props']['report']['current_year_total'];
        expect($currentYearTotal)->toBe(800000);
    });
});

test('report shows last 365 days total', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 1000000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 500000,
        'issue_date' => Carbon::now()->subDays(200),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(2)->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->subDays(400),
    ]);

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $last365DaysTotal = $page->toArray()['props']['report']['last_365_days_total'];
        expect($last365DaysTotal)->toBe(1500000);
    });
});

test('report calculates remaining amount correctly with 6000000 threshold', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 2000000,
        'issue_date' => Carbon::now()->subDays(180),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 1500000,
        'issue_date' => Carbon::now()->subDays(90),
    ]);

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $vatThreshold = $page->toArray()['props']['report']['vat_threshold'];
        $last365DaysTotal = $page->toArray()['props']['report']['last_365_days_total'];
        $remainingAmount = $page->toArray()['props']['report']['remaining_amount'];

        expect($vatThreshold)->toBe(6000000);
        expect($last365DaysTotal)->toBe(3500000);
        expect($remainingAmount)->toBe(2500000);
    });
});

test('report shows remaining amount as zero when threshold exceeded', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 7000000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $remainingAmount = $page->toArray()['props']['report']['remaining_amount'];
        expect($remainingAmount)->toBe(0);
    });
});

test('report shows client structure with totals and percentages', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 500000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->subDays(50),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(30),
    ]);

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($clients) {
        $clientStructure = $page->toArray()['props']['report']['client_structure'];

        expect($clientStructure)->toBeArray();
        expect(count($clientStructure))->toBe(2);

        $client1 = collect($clientStructure)->firstWhere('client_id', $clients->first()->id);
        expect($client1['total'])->toBe(700000);
        expect($client1['percentage'])->toBe(70);

        $client2 = collect($clientStructure)->firstWhere('client_id', $clients->get(1)->id);
        expect($client2['total'])->toBe(300000);
        expect($client2['percentage'])->toBe(30);
    });
});

test('report shows warning when agency has less than 5 clients', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(4)->create();
    $agency->clients()->attach($clients->pluck('id'));

    foreach ($clients as $client) {
        Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'total' => 100000,
            'issue_date' => Carbon::now()->subDays(100),
        ]);
    }

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $warnings = $page->toArray()['props']['report']['warnings'];
        $warningTypes = collect($warnings)->pluck('type')->toArray();

        expect($warningTypes)->toContain('min_clients');
        $minClientsWarning = collect($warnings)->firstWhere('type', 'min_clients');
        expect($minClientsWarning['message'])->toContain('4 client(s)');
        expect($minClientsWarning['message'])->toContain('Minimum required is 5');
    });
});

test('report does not show warning when agency has exactly 5 clients', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    foreach ($clients as $client) {
        Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'total' => 100000,
            'issue_date' => Carbon::now()->subDays(100),
        ]);
    }

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $warnings = $page->toArray()['props']['report']['warnings'];
        $warningTypes = collect($warnings)->pluck('type')->toArray();

        expect($warningTypes)->not->toContain('min_clients');
    });
});

test('report shows warning when single client exceeds 70 percent', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 750000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    foreach ($clients->skip(1) as $client) {
        Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'total' => 62500,
            'issue_date' => Carbon::now()->subDays(100),
        ]);
    }

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $warnings = $page->toArray()['props']['report']['warnings'];
        $warningTypes = collect($warnings)->pluck('type')->toArray();

        expect($warningTypes)->toContain('client_share');
        $clientShareWarning = collect($warnings)->firstWhere('type', 'client_share');
        expect($clientShareWarning['message'])->toContain('75.00%');
        expect($clientShareWarning['message'])->toContain('maximum allowed is 70.00%');
    });
});

test('report does not show warning when client is exactly 70 percent', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 700000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    foreach ($clients->skip(1) as $client) {
        Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'total' => 100000,
            'issue_date' => Carbon::now()->subDays(100),
        ]);
    }

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $warnings = $page->toArray()['props']['report']['warnings'];
        $warningTypes = collect($warnings)->pluck('type')->toArray();

        expect($warningTypes)->not->toContain('client_share');
    });
});

test('report excludes soft deleted invoices from calculations', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(5)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 1000000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    $deletedInvoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 500000,
        'issue_date' => Carbon::now()->subDays(50),
    ]);
    $deletedInvoice->delete();

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $last365DaysTotal = $page->toArray()['props']['report']['last_365_days_total'];
        expect($last365DaysTotal)->toBe(1000000);
    });
});

test('report uses custom settings for thresholds when available', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '5000000',
    ]);
    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'min_clients_per_year',
        'value' => '6',
    ]);
    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '75',
    ]);

    $clients = Client::factory()->count(6)->create();
    $agency->clients()->attach($clients->pluck('id'));

    foreach ($clients as $client) {
        Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'total' => 100000,
            'issue_date' => Carbon::now()->subDays(100),
        ]);
    }

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $vatThreshold = $page->toArray()['props']['report']['vat_threshold'];
        $warnings = $page->toArray()['props']['report']['warnings'];

        expect($vatThreshold)->toBe(5000000);
        expect($warnings)->toBeEmpty();
    });
});

test('authenticated users can view period report', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(2)->create(['is_active' => true]);

    $response = $this->get('/reports/period');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Reports/Period'));
});

test('period report filters invoices by date range and agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(2)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 100000,
        'issue_date' => Carbon::now()->subDays(10),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'total' => 150000,
        'issue_date' => Carbon::now()->subDays(40),
    ]);

    $startDate = Carbon::now()->subDays(15)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalAmount = $page->toArray()['props']['totalAmount'];
        expect($totalAmount)->toBe(300000);
    });
});

