<?php

use App\Models\Agency;
use App\Models\User;

test('guests cannot access agencies index', function () {
    $response = $this->get('/agencies');
    $response->assertRedirect('/login');
});

test('authenticated users can view agencies index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(3)->create();

    $response = $this->get('/agencies');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Agencies/Index'));
});

test('authenticated users can create agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/agencies/create');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Agencies/Create'));
});

test('authenticated users can store agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $data = [
        'name' => 'Test Agency',
        'tax_id' => '105123456',
        'city' => 'Belgrade',
        'country' => 'Serbia',
        'email' => 'test@agency.com',
        'phone' => '+381601234567',
        'invoice_number_prefix' => 'TEST',
    ];

    $response = $this->post('/agencies', $data);
    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', [
        'name' => 'Test Agency',
        'email' => 'test@agency.com',
        'invoice_number_prefix' => 'TEST',
    ]);
});

test('agency creation requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/agencies', []);
    $response->assertSessionHasErrors('name');
});

test('authenticated users can update agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->put("/agencies/{$agency->id}", [
        'name' => 'Updated Agency',
        'tax_id' => $agency->tax_id,
        'city' => $agency->city,
        'country' => $agency->country,
        'email' => 'updated@agency.com',
        'phone' => '+381609999999',
        'invoice_number_prefix' => 'UPD',
        'is_active' => $agency->is_active,
    ]);

    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'name' => 'Updated Agency',
        'email' => 'updated@agency.com',
        'invoice_number_prefix' => 'UPD',
    ]);
});

test('authenticated users can delete agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->delete("/agencies/{$agency->id}");
    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
});

test('authenticated users can toggle agency active status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post("/agencies/{$agency->id}/toggle-status");
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $agency->refresh();
    expect($agency->is_active)->toBeFalse();

    $response = $this->post("/agencies/{$agency->id}/toggle-status");
    $agency->refresh();
    expect($agency->is_active)->toBeTrue();
});

test('guests cannot toggle agency status', function () {
    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/toggle-status");
    $response->assertRedirect('/login');
});

test('guests cannot access agency settings', function () {
    $agency = Agency::factory()->create();

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertRedirect('/login');

    $response = $this->post("/agencies/{$agency->id}/settings");
    $response->assertRedirect('/login');
});

test('authenticated users can view and update agency settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Agencies/Settings')
             ->has('agency')
             ->has('settings')
             ->has('defaultSettings')
    );

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
    ]);

    $response->assertRedirect("/agencies/{$agency->id}/settings");
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '7000000',
    ]);

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '75',
    ]);

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'min_clients_per_year',
        'value' => '6',
    ]);
});

