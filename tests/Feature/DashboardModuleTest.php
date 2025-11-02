<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;

test('guests cannot access dashboard', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can view dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Dashboard'));
});

test('dashboard shows total agencies count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(3)->create(['is_active' => true]);
    Agency::factory()->count(2)->create(['is_active' => false]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $agenciesTotal = $page->toArray()['props']['stats']['agencies']['total'];
        expect($agenciesTotal)->toBe(3);
    });
});

test('dashboard shows total clients count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Client::factory()->count(5)->create();

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $clientsTotal = $page->toArray()['props']['stats']['clients']['total'];
        expect($clientsTotal)->toBe(5);
    });
});

test('dashboard shows total products count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Product::factory()->count(4)->create();

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $productsTotal = $page->toArray()['props']['stats']['products']['total'];
        expect($productsTotal)->toBe(4);
    });
});

test('dashboard shows total invoices count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->count(3)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ])->delete();

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $invoicesTotal = $page->toArray()['props']['stats']['invoices']['total'];
        expect($invoicesTotal)->toBe(3);
    });
});

test('dashboard shows this month invoices count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->count(2)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => Carbon::now()->startOfMonth()->addDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => Carbon::now()->subMonth(),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $thisMonthInvoices = $page->toArray()['props']['stats']['invoices']['thisMonth'];
        expect($thisMonthInvoices)->toBe(2);
    });
});

test('dashboard shows total revenue', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 1000000,
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 500000,
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalRevenue = $page->toArray()['props']['stats']['revenue']['total'];
        expect($totalRevenue)->toBe(1500000);
    });
});

test('dashboard shows this month revenue', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 800000,
        'issue_date' => Carbon::now()->startOfMonth()->addDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->subMonth(),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $thisMonthRevenue = $page->toArray()['props']['stats']['revenue']['thisMonth'];
        expect($thisMonthRevenue)->toBe(800000);
    });
});

test('dashboard shows this year revenue', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 600000,
        'issue_date' => Carbon::now()->startOfYear()->addDays(10),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 400000,
        'issue_date' => Carbon::now()->subYear(),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $thisYearRevenue = $page->toArray()['props']['stats']['revenue']['thisYear'];
        expect($thisYearRevenue)->toBe(600000);
    });
});

test('dashboard shows recent invoices', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->count(7)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($agency, $client) {
        $recentInvoices = $page->toArray()['props']['recentInvoices'];
        expect(count($recentInvoices))->toBe(5);
        expect($recentInvoices[0]['agency_name'])->toBe($agency->name);
        expect($recentInvoices[0]['client_name'])->toBe($client->name);
    });
});

test('dashboard shows monthly revenue breakdown', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 500000,
        'issue_date' => Carbon::now()->startOfYear()->addMonths(0)->addDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 300000,
        'issue_date' => Carbon::now()->startOfYear()->addMonths(1)->addDays(10),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 200000,
        'issue_date' => Carbon::now()->startOfYear()->addMonths(1)->addDays(15),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $monthlyRevenue = $page->toArray()['props']['monthlyRevenue'];
        expect(count($monthlyRevenue))->toBeGreaterThanOrEqual(2);

        $janRevenue = collect($monthlyRevenue)->firstWhere('month', 1);
        expect($janRevenue['revenue'])->toBe(500000);

        $febRevenue = collect($monthlyRevenue)->firstWhere('month', 2);
        expect($febRevenue['revenue'])->toBe(500000);
    });
});

test('dashboard excludes soft deleted invoices from statistics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 1000000,
    ]);

    $deletedInvoice = Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 500000,
    ]);
    $deletedInvoice->delete();

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $totalRevenue = $page->toArray()['props']['stats']['revenue']['total'];
        $invoicesTotal = $page->toArray()['props']['stats']['invoices']['total'];
        
        expect($totalRevenue)->toBe(1000000);
        expect($invoicesTotal)->toBe(1);
    });
});

test('dashboard calculates invoice trend correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $lastMonth = Carbon::now()->subMonth();
    Invoice::factory()->count(5)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => $lastMonth->startOfMonth()->addDays(5),
    ]);

    Invoice::factory()->count(10)->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'issue_date' => Carbon::now()->startOfMonth()->addDays(5),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $invoiceTrend = $page->toArray()['props']['stats']['invoices']['trend'];
        expect($invoiceTrend)->toBe(100);
    });
});

test('dashboard calculates revenue trend correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();
    $client = Client::factory()->create();
    $client->agencies()->attach($agency->id);

    $lastMonth = Carbon::now()->subMonth();
    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 1000000,
        'issue_date' => $lastMonth->startOfMonth()->addDays(5),
    ]);

    Invoice::factory()->create([
        'agency_id' => $agency->id,
        'client_id' => $client->id,
        'total' => 1500000,
        'issue_date' => Carbon::now()->startOfMonth()->addDays(5),
    ]);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(function ($page) {
        $revenueTrend = $page->toArray()['props']['stats']['revenue']['trend'];
        expect($revenueTrend)->toBe(50);
    });
});

