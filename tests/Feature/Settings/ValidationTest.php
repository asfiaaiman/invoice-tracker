<?php

use App\Models\Agency;
use App\Models\User;

test('application settings validates invoice prefix with spaces', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'INV TEST',
    ]);

    $response->assertInvalid(['invoice_number_prefix']);
});

test('application settings validates invoice prefix with special characters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $invalidPrefixes = ['INV@TEST', 'INV.TEST', 'INV#TEST', 'INV$TEST', 'INV%TEST'];

    foreach ($invalidPrefixes as $prefix) {
        $response = $this->post('/settings/application', [
            'agency_id' => $agency->id,
            'pdv_limit' => '6000000',
            'client_max_share_percent' => '70',
            'min_clients_per_year' => '5',
            'invoice_number_prefix' => $prefix,
        ]);

        $response->assertInvalid(['invoice_number_prefix']);
    }
});

test('application settings validates invoice prefix max length', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $longPrefix = str_repeat('A', 21); // 21 characters

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => $longPrefix,
    ]);

    $response->assertInvalid(['invoice_number_prefix']);
});

test('application settings accepts invoice prefix at max length', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $maxPrefix = str_repeat('A', 20); // 20 characters (max allowed)

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => $maxPrefix,
    ]);

    $response->assertSessionHasNoErrors();
    expect($agency->fresh()->invoice_number_prefix)->toBe($maxPrefix);
});

test('agency settings validates invoice prefix format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $invalidPrefixes = ['INV TEST', 'INV@TEST', 'INV.TEST', 'INV#TEST'];

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

test('application settings validates pdv_limit is numeric', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => 'not-a-number',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
    ]);

    $response->assertInvalid(['pdv_limit']);
});

test('application settings validates pdv_limit minimum value', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '-1000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
    ]);

    $response->assertInvalid(['pdv_limit']);
});

test('application settings validates client_max_share_percent range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    // Test over 100%
    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '101',
        'min_clients_per_year' => '5',
    ]);

    $response->assertInvalid(['client_max_share_percent']);

    // Test negative
    $response2 = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '-1',
        'min_clients_per_year' => '5',
    ]);

    $response2->assertInvalid(['client_max_share_percent']);
});

test('application settings validates min_clients_per_year minimum value', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '0',
    ]);

    $response->assertInvalid(['min_clients_per_year']);

    $response2 = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '-1',
    ]);

    $response2->assertInvalid(['min_clients_per_year']);
});

test('application settings accepts valid boundary values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    // Test minimum values
    $response = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '0',
        'client_max_share_percent' => '0',
        'min_clients_per_year' => '1',
    ]);

    $response->assertSessionHasNoErrors();

    // Test maximum values
    $response2 = $this->post('/settings/application', [
        'agency_id' => $agency->id,
        'pdv_limit' => '999999999',
        'client_max_share_percent' => '100',
        'min_clients_per_year' => '999',
    ]);

    $response2->assertSessionHasNoErrors();
});

test('agency settings validates all required fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    // Missing pdv_limit
    $response = $this->post("/agencies/{$agency->id}/settings", [
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
    ]);

    $response->assertInvalid(['pdv_limit']);

    // Missing client_max_share_percent
    $response2 = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'min_clients_per_year' => '5',
    ]);

    $response2->assertInvalid(['client_max_share_percent']);

    // Missing min_clients_per_year
    $response3 = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
    ]);

    $response3->assertInvalid(['min_clients_per_year']);
});

