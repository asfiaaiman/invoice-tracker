<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;

test('guests cannot access clients', function () {
    $response = $this->get('/clients');
    $response->assertRedirect('/login');

    $client = Client::factory()->create();
    $response = $this->get("/clients/{$client->id}");
    $response->assertRedirect('/login');
});

test('authenticated users can view clients index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Client::factory()->count(3)->create();

    $response = $this->get('/clients');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Clients/Index'));
});

test('authenticated users can view create client page with agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Agency::factory()->count(2)->create(['is_active' => true]);
    Agency::factory()->create(['is_active' => false]);

    $response = $this->get('/clients/create');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Clients/Create')
             ->has('agencies', 2)
    );
});

test('authenticated users can create client with single agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Test Client',
        'tax_id' => '200123456',
        'email' => 'client@example.com',
        'phone' => '+381601234567',
        'address' => 'Client Street 456',
        'city' => 'Belgrade',
        'zip_code' => '11000',
        'country' => 'Serbia',
        'agency_ids' => [$agency->id],
    ];

    $response = $this->post('/clients', $data);
    $response->assertRedirect('/clients');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('clients', [
        'name' => 'Test Client',
        'tax_id' => '200123456',
        'email' => 'client@example.com',
    ]);

    $client = Client::where('name', 'Test Client')->first();
    expect($client->agencies)->toHaveCount(1);
    expect($client->agencies->first()->id)->toBe($agency->id);
});

test('authenticated users can create client with multiple agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agencies = Agency::factory()->count(3)->create(['is_active' => true]);

    $data = [
        'name' => 'Multi-Agency Client',
        'tax_id' => '200654321',
        'agency_ids' => $agencies->pluck('id')->toArray(),
    ];

    $response = $this->post('/clients', $data);
    $response->assertRedirect('/clients');
    $response->assertSessionHas('success');

    $client = Client::where('name', 'Multi-Agency Client')->first();
    expect($client->agencies)->toHaveCount(3);
    expect($client->agencies->pluck('id')->toArray())->toEqual($agencies->pluck('id')->toArray());
});

test('client creation requires at least one agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/clients', [
        'name' => 'Test Client',
        'agency_ids' => [],
    ]);

    $response->assertSessionHasErrors('agency_ids');
});

test('client creation requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/clients', [
        'agency_ids' => [$agency->id],
    ]);

    $response->assertSessionHasErrors('name');
});

test('client creation validates email format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post('/clients', [
        'name' => 'Test Client',
        'email' => 'invalid-email',
        'agency_ids' => [$agency->id],
    ]);

    $response->assertSessionHasErrors('email');
});

test('client creation validates agency exists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/clients', [
        'name' => 'Test Client',
        'agency_ids' => [99999],
    ]);

    $response->assertSessionHasErrors('agency_ids.0');
});

test('authenticated users can view client show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();
    $agencies = Agency::factory()->count(2)->create();
    $client->agencies()->attach($agencies->pluck('id'));

    $response = $this->get("/clients/{$client->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Clients/Show'));
});

test('authenticated users can view client edit page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();
    $agency = Agency::factory()->create(['is_active' => true]);
    $client->agencies()->attach($agency->id);

    $response = $this->get("/clients/{$client->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Clients/Edit'));
});

test('authenticated users can update client with changed agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();
    $agency1 = Agency::factory()->create(['is_active' => true]);
    $agency2 = Agency::factory()->create(['is_active' => true]);
    $agency3 = Agency::factory()->create(['is_active' => true]);
    $client->agencies()->attach($agency1->id);

    $response = $this->put("/clients/{$client->id}", [
        'name' => 'Updated Client Name',
        'tax_id' => $client->tax_id,
        'email' => $client->email,
        'agency_ids' => [$agency2->id, $agency3->id],
    ]);

    $response->assertRedirect('/clients');
    $response->assertSessionHas('success');

    $client->refresh();
    expect($client->agencies)->toHaveCount(2);
    expect($client->agencies->pluck('id')->toArray())->not->toContain($agency1->id);
    expect($client->agencies->pluck('id')->toArray())->toContain($agency2->id);
    expect($client->agencies->pluck('id')->toArray())->toContain($agency3->id);
});

test('authenticated users can delete client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();

    $response = $this->delete("/clients/{$client->id}");
    $response->assertRedirect('/clients');
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
});

test('client index shows agencies relationship', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create();
    $agencies = Agency::factory()->count(2)->create();
    $client->agencies()->attach($agencies->pluck('id'));

    $response = $this->get('/clients');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->has('clients.data')
    );
});

test('authenticated users can create client with note field', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Client With Note',
        'note' => 'This is an important note about the client',
        'agency_ids' => [$agency->id],
    ];

    $response = $this->post('/clients', $data);
    $response->assertRedirect('/clients');

    $this->assertDatabaseHas('clients', [
        'name' => 'Client With Note',
        'note' => 'This is an important note about the client',
    ]);
});

test('authenticated users can update client note field', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::factory()->create(['note' => 'Original note']);
    $agency = Agency::factory()->create(['is_active' => true]);
    $client->agencies()->attach($agency->id);

    $response = $this->put("/clients/{$client->id}", [
        'name' => $client->name,
        'note' => 'Updated note',
        'agency_ids' => [$agency->id],
    ]);

    $response->assertRedirect('/clients');
    $client->refresh();
    expect($client->note)->toBe('Updated note');
});

test('client note field is optional', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $data = [
        'name' => 'Client Without Note',
        'agency_ids' => [$agency->id],
    ];

    $response = $this->post('/clients', $data);
    $response->assertRedirect('/clients');

    $client = Client::where('name', 'Client Without Note')->first();
    expect($client->note)->toBeNull();
});

