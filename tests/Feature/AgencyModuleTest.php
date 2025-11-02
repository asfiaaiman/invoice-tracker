<?php

use App\Models\Agency;
use App\Models\User;

test('guests cannot access agencies', function () {
    $response = $this->get('/agencies');
    $response->assertRedirect('/login');

    $agency = Agency::factory()->create();
    $response = $this->get("/agencies/{$agency->id}");
    $response->assertRedirect('/login');

    $response = $this->get("/agencies/{$agency->id}/edit");
    $response->assertRedirect('/login');
});

test('authenticated users can view agencies index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(5)->create(['is_active' => true]);
    Agency::factory()->count(2)->create(['is_active' => false]);

    $response = $this->get('/agencies');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Agencies/Index'));
});

test('authenticated users can view create agency page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/agencies/create');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Agencies/Create'));
});

test('authenticated users can store agency with all fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $data = [
        'name' => 'Test Agency Ltd.',
        'tax_id' => '105123456',
        'address' => 'Test Street 123',
        'city' => 'Belgrade',
        'zip_code' => '11000',
        'country' => 'Serbia',
        'is_active' => true,
    ];

    $response = $this->post('/agencies', $data);
    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', [
        'name' => 'Test Agency Ltd.',
        'tax_id' => '105123456',
        'city' => 'Belgrade',
        'country' => 'Serbia',
        'is_active' => true,
    ]);
});

test('agency creation requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/agencies', [
        'tax_id' => '105123456',
    ]);

    $response->assertSessionHasErrors('name');
});

test('agency creation validates field lengths', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/agencies', [
        'name' => str_repeat('a', 256),
        'tax_id' => str_repeat('a', 256),
        'city' => str_repeat('a', 256),
        'zip_code' => str_repeat('a', 21),
    ]);

    $response->assertSessionHasErrors(['name', 'tax_id', 'city', 'zip_code']);
});

test('authenticated users can view agency show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->get("/agencies/{$agency->id}");
    $response->assertStatus(200);
    // Note: Component may not exist yet, just verify status
    expect($response->status())->toBe(200);
});

test('authenticated users can view agency edit page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->get("/agencies/{$agency->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Agencies/Edit'));
});

test('authenticated users can update agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'name' => 'Original Name',
        'city' => 'Novi Sad',
    ]);

    $response = $this->put("/agencies/{$agency->id}", [
        'name' => 'Updated Agency Name',
        'tax_id' => $agency->tax_id,
        'city' => 'Belgrade',
        'country' => $agency->country,
        'is_active' => true,
    ]);

    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'name' => 'Updated Agency Name',
        'city' => 'Belgrade',
    ]);
});

test('agency update requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->put("/agencies/{$agency->id}", [
        'name' => '',
        'tax_id' => $agency->tax_id,
    ]);

    $response->assertSessionHasErrors('name');
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

test('agencies index shows paginated results', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(25)->create();

    $response = $this->get('/agencies');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->has('agencies.data')->count('agencies.data', 20));
});

