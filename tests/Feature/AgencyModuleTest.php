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
        'email' => 'contact@testagency.com',
        'phone' => '+381601234567',
        'website' => 'https://testagency.com',
        'invoice_number_prefix' => 'TST',
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
        'email' => 'contact@testagency.com',
        'phone' => '+381601234567',
        'website' => 'https://testagency.com',
        'invoice_number_prefix' => 'TST',
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

test('authenticated users can create agency with business information', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $data = [
        'name' => 'Business Agency',
        'email' => 'info@business.com',
        'phone' => '+381111234567',
        'website' => 'https://business.com',
        'invoice_number_prefix' => 'BUS',
    ];

    $response = $this->post('/agencies', $data);
    $response->assertRedirect('/agencies');

    $this->assertDatabaseHas('agencies', [
        'name' => 'Business Agency',
        'email' => 'info@business.com',
        'phone' => '+381111234567',
        'website' => 'https://business.com',
        'invoice_number_prefix' => 'BUS',
    ]);
});

test('agency creation validates email format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/agencies', [
        'name' => 'Test Agency',
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('agency creation validates website URL', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/agencies', [
        'name' => 'Test Agency',
        'website' => 'not-a-url',
    ]);

    $response->assertSessionHasErrors('website');
});

test('agency creation validates invoice prefix length', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/agencies', [
        'name' => 'Test Agency',
        'invoice_number_prefix' => str_repeat('A', 21),
    ]);

    $response->assertSessionHasErrors('invoice_number_prefix');
});

test('authenticated users can toggle agency status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create(['is_active' => true]);

    $response = $this->post("/agencies/{$agency->id}/toggle-status");
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'is_active' => false,
    ]);

    $response = $this->post("/agencies/{$agency->id}/toggle-status");
    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'is_active' => true,
    ]);
});

test('authenticated users can update agency with new business fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'email' => 'old@email.com',
        'invoice_number_prefix' => 'OLD',
    ]);

    $response = $this->put("/agencies/{$agency->id}", [
        'name' => $agency->name,
        'tax_id' => $agency->tax_id,
        'email' => 'new@email.com',
        'phone' => '+381601111111',
        'website' => 'https://newwebsite.com',
        'invoice_number_prefix' => 'NEW',
        'country' => $agency->country,
        'is_active' => $agency->is_active,
    ]);

    $response->assertRedirect('/agencies');
    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'email' => 'new@email.com',
        'phone' => '+381601111111',
        'website' => 'https://newwebsite.com',
        'invoice_number_prefix' => 'NEW',
    ]);
});

test('guests cannot access agency settings', function () {
    $agency = Agency::factory()->create();

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertRedirect('/login');
});

test('authenticated users can view agency settings', function () {
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
});

test('agency settings page shows default values when no settings exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $defaultSettings = $page->toArray()['props']['defaultSettings'];
        expect($defaultSettings['pdv_limit'])->toBe('6000000');
        expect($defaultSettings['client_max_share_percent'])->toBe('70');
        expect($defaultSettings['min_clients_per_year'])->toBe('5');
        expect($defaultSettings['invoice_number_prefix'])->toBe('INV');
    });
});

test('agency settings page shows existing settings when they exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'invoice_number_prefix' => 'CUSTOM',
    ]);
    
    \App\Models\Setting::create([
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '7000000',
    ]);
    
    \App\Models\Setting::create([
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '75',
    ]);

    $response = $this->get("/agencies/{$agency->id}/settings");
    $response->assertStatus(200);
    $response->assertInertia(function ($page) {
        $settings = $page->toArray()['props']['settings'];
        expect($settings['pdv_limit'])->toBe('7000000');
        expect($settings['client_max_share_percent'])->toBe('75');
        expect($settings['min_clients_per_year'])->toBe('5');
        expect($settings['invoice_number_prefix'])->toBe('CUSTOM');
    });
});

test('authenticated users can update agency settings with all settings including invoice prefix', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
        'invoice_number_prefix' => 'CUSTOM',
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

    $this->assertDatabaseHas('agencies', [
        'id' => $agency->id,
        'invoice_number_prefix' => 'CUSTOM',
    ]);
});

test('agency settings update requires all three fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => 7000000,
    ]);

    $response->assertStatus(302);
    $response->assertInvalid(['client_max_share_percent', 'min_clients_per_year']);
});

test('agency settings update validates numeric values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => 'not-a-number',
        'client_max_share_percent' => 'not-a-number',
        'min_clients_per_year' => 'not-a-number',
    ]);

    $response->assertStatus(302);
    $response->assertInvalid(['pdv_limit', 'client_max_share_percent', 'min_clients_per_year']);
});

test('agency settings update validates client max share percent range', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => 7000000,
        'client_max_share_percent' => 150,
        'min_clients_per_year' => 5,
    ]);

    $response->assertInvalid('client_max_share_percent');
});

test('agency settings update validates min clients per year minimum', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => 7000000,
        'client_max_share_percent' => 70,
        'min_clients_per_year' => 0,
    ]);

    $response->assertInvalid('min_clients_per_year');
});

test('agency settings update accepts numeric values converted to strings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
    ]);

    $response->assertRedirect(route('agencies.settings', $agency));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '7000000',
    ]);
});

test('agency settings can be updated multiple times', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create();

    $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
    ]);

    $response = $this->post("/agencies/{$agency->id}/settings", [
        'pdv_limit' => '8000000',
        'client_max_share_percent' => '80',
        'min_clients_per_year' => '7',
    ]);

    $response->assertRedirect("/agencies/{$agency->id}/settings");
    
    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '8000000',
    ]);
    
    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '80',
    ]);
});

test('agency settings are agency-specific', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();

    $this->post("/agencies/{$agency1->id}/settings", [
        'pdv_limit' => '7000000',
        'client_max_share_percent' => '75',
        'min_clients_per_year' => '6',
    ]);

    $this->post("/agencies/{$agency2->id}/settings", [
        'pdv_limit' => '9000000',
        'client_max_share_percent' => '85',
        'min_clients_per_year' => '8',
    ]);

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency1->id,
        'key' => 'pdv_limit',
        'value' => '7000000',
    ]);

    $this->assertDatabaseHas('settings', [
        'agency_id' => $agency2->id,
        'key' => 'pdv_limit',
        'value' => '9000000',
    ]);

    $settingsCount1 = \App\Models\Setting::where('agency_id', $agency1->id)->count();
    $settingsCount2 = \App\Models\Setting::where('agency_id', $agency2->id)->count();
    
    expect($settingsCount1)->toBe(3);
    expect($settingsCount2)->toBe(3);
});

test('agencies index displays contact information', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $agency = Agency::factory()->create([
        'email' => 'test@agency.com',
        'phone' => '+381601234567',
        'is_active' => true,
    ]);

    $response = $this->get('/agencies');
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => 
        $page->component('Agencies/Index')
             ->has('agencies.data')
    );
    
    $pageData = $response->original->getData()['page'];
    $agencies = $pageData['props']['agencies']['data'];
    $found = collect($agencies)->first(function($a) {
        return $a['email'] === 'test@agency.com' &&
               $a['phone'] === '+381601234567' &&
               $a['is_active'] === true;
    });
    
    expect($found)->not->toBeNull();
});


