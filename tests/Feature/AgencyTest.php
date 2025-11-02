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
    ];

    $response = $this->post('/agencies', $data);
    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', ['name' => 'Test Agency']);
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
    ]);

    $response->assertRedirect('/agencies');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'name' => 'Updated Agency',
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

