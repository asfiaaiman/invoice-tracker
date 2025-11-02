<?php

use App\Models\Agency;
use App\Models\Setting;
use App\Models\User;

test('guests cannot access application settings', function () {
    $response = $this->get('/settings/application');
    $response->assertRedirect('/login');

    $response = $this->post('/settings/application');
    $response->assertRedirect('/login');
});

test('authenticated users can view application settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(2)->create(['is_active' => true]);

    $response = $this->get('/settings/application');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('settings/Application')
             ->has('agencies')
             ->has('settings')
             ->has('defaultSettings')
    );
});

test('application settings shows all active agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create(['is_active' => true, 'name' => 'Agency 1']);
    $agency2 = Agency::factory()->create(['is_active' => true, 'name' => 'Agency 2']);
    Agency::factory()->create(['is_active' => false, 'name' => 'Inactive Agency']);

    $response = $this->get('/settings/application');
    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($agency1, $agency2) {
        $agencies = $page->toArray()['props']['agencies'];
        expect($agencies)->toBeArray();
        expect(count($agencies))->toBe(2);
        expect(collect($agencies)->pluck('name')->toArray())->not->toContain('Inactive Agency');
    });
});

test('application settings shows default values for agencies without settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create agency with default prefix to match expected behavior
    $agency = Agency::factory()->create(['is_active' => true, 'invoice_number_prefix' => 'INV']);

    $response = $this->get('/settings/application');
    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($agency) {
        $settings = $page->toArray()['props']['settings'];
        $defaultSettings = $page->toArray()['props']['defaultSettings'];
        
        expect($settings[$agency->id]['pdv_limit'])->toBe($defaultSettings['pdv_limit']);
        expect($settings[$agency->id]['client_max_share_percent'])->toBe($defaultSettings['client_max_share_percent']);
        expect($settings[$agency->id]['min_clients_per_year'])->toBe($defaultSettings['min_clients_per_year']);
        expect($settings[$agency->id]['invoice_number_prefix'])->toBe($defaultSettings['invoice_number_prefix']);
    });
});

test('application settings shows custom values when settings exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'CUSTOM',
    ]);

    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '8000000',
    ]);

    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '80',
    ]);

    $response = $this->get('/settings/application');
    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($agency) {
        $settings = $page->toArray()['props']['settings'];
        
        expect($settings[$agency->id]['pdv_limit'])->toBe('8000000');
        expect($settings[$agency->id]['client_max_share_percent'])->toBe('80');
        expect($settings[$agency->id]['invoice_number_prefix'])->toBe('CUSTOM');
    });
});

test('authenticated users can update application settings for agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
        'invoice_number_prefix' => 'CUSTOM',
    ]);

    $response->assertRedirect('/settings/application');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '7000000',
    ]);

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'invoice_number_prefix' => 'CUSTOM',
    ]);
});

test('application settings update requires agency_id', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/settings/application', [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
    ]);

    $response->assertInvalid(['agency_id']);
});

test('application settings validates invoice prefix format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    // Invalid characters (spaces, special chars)
    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'INV TEST',
    ]);

    $response->assertInvalid(['invoice_number_prefix']);

    // Invalid characters (special chars)
    $response2 = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'INV@TEST',
    ]);

    $response2->assertInvalid(['invoice_number_prefix']);
});

test('application settings accepts valid invoice prefix formats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $validPrefixes = ['INV', 'INV-2024', 'AGENCY_1', 'TEST123', 'A-B-C'];

    foreach ($validPrefixes as $prefix) {
        $response = $this->post('/settings/application', [
            'agency_id' => $agency->id,
            'pdv_limit' => '6000000',
            'client_max_share_percent' => '70',
            'min_clients_per_year' => '5',
            'invoice_number_prefix' => $prefix,
        ]);

        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'invoice_number_prefix' => strtoupper($prefix),
        ]);
    }
});

test('application settings converts invoice prefix to uppercase', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'lowercase',
    ]);

    $response->assertSessionHasNoErrors();
    
    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'invoice_number_prefix' => 'LOWERCASE',
    ]);
});

test('application settings resets invoice prefix to default when empty', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'OLD',
    ]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => '',
    ]);

    $response->assertSessionHasNoErrors();
    
    // Empty string should be converted to default 'INV'
    expect($agency->fresh()->invoice_number_prefix)->toBe('INV');
});

test('application settings validates numeric ranges', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    // Invalid: negative values
    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '-1000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
    ]);

    $response->assertInvalid(['pdv_limit']);

    // Invalid: client share > 100
    $response2 = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '101',
        'min_clients_per_year' => '5',
    ]);

    $response2->assertInvalid(['client_max_share_percent']);

    // Invalid: min_clients < 1
    $response3 = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '0',
    ]);

    $response3->assertInvalid(['min_clients_per_year']);
});

test('application settings update persists all settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '7500000',
        'client_max_share_percent' => '85',
        'min_clients_per_year' => '7',
        'invoice_number_prefix' => 'TEST',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '7500000',
    ]);

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '85',
    ]);

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'min_clients_per_year',
        'value' => '7',
    ]);

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'invoice_number_prefix' => 'TEST',
    ]);
});

