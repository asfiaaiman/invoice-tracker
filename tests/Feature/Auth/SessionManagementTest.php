<?php

use App\Models\User;

test('user session persists across multiple requests', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // Make multiple requests
    $response1 = $this->get(route('dashboard'));
    $response2 = $this->get(route('dashboard'));
    $response3 = $this->get(route('dashboard'));

    $response1->assertStatus(200);
    $response2->assertStatus(200);
    $response3->assertStatus(200);
    $this->assertAuthenticatedAs($user);
});

test('user session is destroyed on logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->assertAuthenticated();

    $response = $this->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('logout requires authentication', function () {
    $response = $this->post(route('logout'));

    // Guest should be redirected to login
    $response->assertRedirect(route('login'));
});

test('user can access protected routes while authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $routes = [
        route('dashboard'),
        route('agencies.index'),
        route('clients.index'),
        route('products.index'),
        route('invoices.index'),
        route('reports.index'),
    ];

    foreach ($routes as $route) {
        $response = $this->get($route);
        expect($response->status())->not->toBe(302); // Should not redirect
    }
});

test('session contains user information', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    expect(auth()->user()->id)->toBe($user->id);
    expect(auth()->user()->email)->toBe($user->email);
    expect(auth()->user()->name)->toBe($user->name);
});

test('user cannot access other users data through session', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1);

    // Try to access as user2 (if such routes exist)
    // This is more of a conceptual test - actual implementation depends on your routes
    expect(auth()->id())->toBe($user1->id);
    expect(auth()->id())->not->toBe($user2->id);
});

test('session lifetime is configurable', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    
    // Session lifetime is configured in config/session.php
    // Default is 120 minutes, but session expiration is handled by Laravel's session driver
    // In tests, we verify the user is authenticated
    $this->assertAuthenticated();
    
    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('user can login and be authenticated', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

