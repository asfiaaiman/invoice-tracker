<?php

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;

test('authenticated users can create client with multiple agencies', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agencies = Agency::factory()->count(2)->create();

    $data = [
        'name' => 'Test Client',
        'tax_id' => '200123456',
        'email' => 'client@example.com',
        'agency_ids' => $agencies->pluck('id')->toArray(),
    ];

    $response = $this->post('/clients', $data);
    $response->assertRedirect('/clients');
    $response->assertSessionHas('success');

    $client = Client::where('name', 'Test Client')->first();
    expect($client->agencies)->toHaveCount(2);
});

test('client requires at least one agency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/clients', [
        'name' => 'Test Client',
        'agency_ids' => [],
    ]);

    $response->assertSessionHasErrors('agency_ids');
});

