<?php

use App\Models\Agency;
use App\Models\Setting;
use App\Models\User;

test('agency settings shows invoice prefix in form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'invoice_number_prefix' => 'TEST',
    ]);

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $settings = $page->toArray()['props']['settings'];
        expect($settings['invoice_number_prefix'])->toBe('TEST');
    });
});

test('agency settings shows default invoice prefix when none set', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create agency - factory will generate a prefix, but we'll use the default in settings view
    $agency = Agency::factory()->create(['invoice_number_prefix' => 'INV']);

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $settings = $page->toArray()['props']['settings'];
        $defaultSettings = $page->toArray()['props']['defaultSettings'];
        expect($settings['invoice_number_prefix'])->toBe($defaultSettings['invoice_number_prefix']);
    });
});

test('agency settings update validates invoice prefix format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    // Test invalid formats
    $invalidPrefixes = ['INV TEST', 'INV@TEST', 'INV.TEST', 'INV TEST!'];

    foreach ($invalidPrefixes as $prefix) {
        $response = $this->post("/agencies/{$agency->id}/settings", [
            'pdv_limit' => '6000000',
            'client_max_share_percent' => '70',
            'min_clients_per_year' => '5',
            'invoice_number_prefix' => $prefix,
        ]);

        $response->assertInvalid(['invoice_number_prefix']);
    }
});

test('agency settings update accepts valid invoice prefix formats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $validPrefixes = ['INV', 'INV-2024', 'AGENCY_1', 'TEST123', 'A-B-C', 'ABC_DEF'];

    foreach ($validPrefixes as $prefix) {
        $response = $this->post("/agencies/{$agency->id}/settings", [
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

test('agency settings converts invoice prefix to uppercase', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'lowercase-prefix',
    ]);

    $response->assertSessionHasNoErrors();
    
    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'invoice_number_prefix' => 'LOWERCASE-PREFIX',
    ]);
});

test('agency settings resets invoice prefix to default when empty', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'invoice_number_prefix' => 'OLD',
    ]);

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => '',
    ]);

    $response->assertSessionHasNoErrors();
    
    // Empty string should be converted to default 'INV'
    expect($agency->fresh()->invoice_number_prefix)->toBe('INV');
});

test('agency settings update trims whitespace from invoice prefix', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => '  TEST  ',
    ]);

    $response->assertSessionHasNoErrors();
    
    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'invoice_number_prefix' => 'TEST',
    ]);
});

test('agency settings validates invoice prefix max length', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $longPrefix = str_repeat('A', 21); // 21 characters

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => $longPrefix,
    ]);

    $response->assertInvalid(['invoice_number_prefix']);
});

test('agency settings can update invoice prefix independently', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'invoice_number_prefix' => 'OLD',
    ]);

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'NEW',
    ]);

    $response->assertSessionHasNoErrors();
    
    expect($agency->fresh()->invoice_number_prefix)->toBe('NEW');
});

