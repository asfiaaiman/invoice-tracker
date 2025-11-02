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

test('guests cannot access period report', function () {
    $response = $this->get('/reports/period');
    $response->assertRedirect('/login');
});

test('period report shows agencies list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create(['is_active' => true, 'name' => 'Agency 1']);
    $agency2 = Agency::factory()->create(['is_active' => true, 'name' => 'Agency 2']);
    Agency::factory()->create(['is_active' => false, 'name' => 'Inactive Agency']);

    $response = $this->get('/reports/period');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $agencies = $page->toArray()['props']['agencies'];
        expect($agencies)->toBeArray();
        expect(count($agencies))->toBe(2);
        expect(collect($agencies)->pluck('name')->toArray())->not->toContain('Inactive Agency');
    });
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
        $invoices = $page->toArray()['props']['invoices'];
        expect($totalAmount)->toBe(300000);
        expect(count($invoices))->toBe(2);
    });
});

test('period report shows client summary with totals and percentages', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client1 = Client::factory()->create(['name' => 'Client 1']);
    $client2 = Client::factory()->create(['name' => 'Client 2']);
    $agency->clients()->attach([$client1->id, $client2->id]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client1->id,
        'total' => 500000,
        'issue_date' => Carbon::now()->subDays(10),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client1->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client2->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->subDays(8),
    ]);

    $startDate = Carbon::now()->subDays(15)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($client1, $client2) {
        $clientSummary = $page->toArray()['props']['clientSummary'];

        expect($clientSummary)->toBeArray();
        expect(count($clientSummary))->toBe(2);

        $client1Summary = collect($clientSummary)->firstWhere('client_id', $client1->id);
        expect($client1Summary['total'])->toBe(700000);
        expect($client1Summary['percentage'])->toBe(70);
        expect($client1Summary['invoice_count'])->toBe(2);
        expect($client1Summary['client_name'])->toBe('Client 1');

        $client2Summary = collect($clientSummary)->firstWhere('client_id', $client2->id);
        expect($client2Summary['total'])->toBe(300000);
        expect($client2Summary['percentage'])->toBe(30);
        expect($client2Summary['invoice_count'])->toBe(1);
        expect($client2Summary['client_name'])->toBe('Client 2');
    });
});

test('period report excludes invoices outside date range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $agency->clients()->attach($client->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 100000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(50),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->addDays(5),
    ]);

    $startDate = Carbon::now()->subDays(10)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalAmount = $page->toArray()['props']['totalAmount'];
        $invoices = $page->toArray()['props']['invoices'];
        expect($totalAmount)->toBe(100000);
        expect(count($invoices))->toBe(1);
    });
});

test('period report excludes soft deleted invoices', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $agency->clients()->attach($client->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 100000,
        'issue_date' => Carbon::now()->subDays(10),
    ]);

    $deletedInvoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);
    $deletedInvoice->delete();

    $startDate = Carbon::now()->subDays(15)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalAmount = $page->toArray()['props']['totalAmount'];
        $invoices = $page->toArray()['props']['invoices'];
        expect($totalAmount)->toBe(100000);
        expect(count($invoices))->toBe(1);
    });
});

test('period report shows empty results when no invoices match', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $startDate = Carbon::now()->subDays(15)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalAmount = $page->toArray()['props']['totalAmount'];
        $invoices = $page->toArray()['props']['invoices'];
        $clientSummary = $page->toArray()['props']['clientSummary'];
        expect($totalAmount)->toBe(0);
        expect($invoices)->toBeEmpty();
        expect($clientSummary)->toBeEmpty();
    });
});

test('period report handles missing parameters gracefully', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->create(['is_active' => true]);

    $response = $this->get('/reports/period');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalAmount = $page->toArray()['props']['totalAmount'];
        $invoices = $page->toArray()['props']['invoices'];
        $clientSummary = $page->toArray()['props']['clientSummary'];
        expect($totalAmount)->toBe(0);
        expect($invoices)->toBeEmpty();
        expect($clientSummary)->toBeEmpty();
    });
});

test('period report shows invoices ordered by date descending', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $agency->clients()->attach($client->id);

    $invoice1 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 100000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    $invoice2 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(3),
    ]);

    $invoice3 = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->subDays(1),
    ]);

    $startDate = Carbon::now()->subDays(10)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($invoice1, $invoice2, $invoice3) {
        $invoices = $page->toArray()['props']['invoices'];
        expect(count($invoices))->toBe(3);
        expect($invoices[0]['id'])->toBe($invoice3->id);
        expect($invoices[1]['id'])->toBe($invoice2->id);
        expect($invoices[2]['id'])->toBe($invoice1->id);
    });
});

test('period report filters by correct agency only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();
    $client = Client::factory()->create();
    $agency1->clients()->attach($client->id);
    $agency2->clients()->attach($client->id);

    Invoice::factory()->create([
        'agency_id' => $agency1->id,
        'client_id' => $client->id,
        'total' => 100000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency2->id,
        'client_id' => $client->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->subDays(3),
    ]);

    $startDate = Carbon::now()->subDays(10)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency1->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($agency1) {
        $totalAmount = $page->toArray()['props']['totalAmount'];
        $invoices = $page->toArray()['props']['invoices'];
        expect($totalAmount)->toBe(100000);
        expect(count($invoices))->toBe(1);
        expect($invoices[0]['agency_id'])->toBe($agency1->id);
    });
});

test('period report includes invoice details with client and agency information', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['name' => 'Test Agency']);
    $client = Client::factory()->create(['name' => 'Test Client']);
    $agency->clients()->attach($client->id);

    $invoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-2024-0001',
        'total' => 100000,
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    $startDate = Carbon::now()->subDays(10)->format('Y-m-d');
    $endDate = Carbon::now()->format('Y-m-d');

    $response = $this->get("/reports/period?agency_id={$agency->id}&start_date={$startDate}&end_date={$endDate}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($invoice, $client, $agency) {
        $invoices = $page->toArray()['props']['invoices'];
        expect(count($invoices))->toBe(1);
        expect($invoices[0]['id'])->toBe($invoice->id);
        expect($invoices[0]['invoice_number'])->toBe('INV-2024-0001');
        expect((float) $invoices[0]['total'])->toBe(100000.0);
        expect($invoices[0]['client']['name'])->toBe('Test Client');
        expect($invoices[0]['agency']['name'])->toBe('Test Agency');
    });
});

test('dashboard shows comprehensive statistics and metrics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(2)->create(['is_active' => true]);
    Client::factory()->count(5)->create();

    Invoice::factory()->count(10)->create([
        'issue_date' => Carbon::now()->subDays(5),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) =>
        $page->component('Dashboard')
             ->has('stats.agencies.total')
             ->has('stats.clients.total')
             ->has('stats.invoices.total')
             ->has('stats.revenue.total')
    );
});

test('dashboard shows current year and last 365 days revenue', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $startOfYear = Carbon::now()->startOfYear();
    $last365Days = Carbon::now()->subDays(365);

    Invoice::factory()->create([
        'total' => 500000,
        'issue_date' => $startOfYear->copy()->addDays(10),
    ]);

    Invoice::factory()->create([
        'total' => 300000,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    Invoice::factory()->create([
        'total' => 200000,
        'issue_date' => Carbon::now()->subYear(),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $revenue = $page->toArray()['props']['stats']['revenue'];
        expect($revenue['thisYear'])->toBe(800000);
        expect($revenue['last365Days'])->toBe(800000);
    });
});

test('dashboard shows agency revenue overview', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);

    Invoice::factory()->count(5)->create(['agency_id' => $agency1->id]);
    Invoice::factory()->count(3)->create(['agency_id' => $agency2->id]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $agencyStats = $page->toArray()['props']['agencyStats'];
        expect($agencyStats)->toBeArray();
        expect(count($agencyStats))->toBe(2);
    });
});

test('report client structure includes invoice count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $clients = Client::factory()->count(3)->create();
    $agency->clients()->attach($clients->pluck('id'));

    Invoice::factory()->count(5)->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->first()->id,
        'issue_date' => Carbon::now()->subDays(100),
    ]);

    Invoice::factory()->count(2)->create([
        'agency_id' => $agency->id,
        'client_id' => $clients->get(1)->id,
        'issue_date' => Carbon::now()->subDays(50),
    ]);

    $response = $this->get("/reports/{$agency->id}");
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($clients) {
        $clientStructure = $page->toArray()['props']['report']['client_structure'];

        $client1 = collect($clientStructure)->firstWhere('client_id', $clients->first()->id);
        expect($client1['count'])->toBe(5);

        $client2 = collect($clientStructure)->firstWhere('client_id', $clients->get(1)->id);
        expect($client2['count'])->toBe(2);
    });
});

