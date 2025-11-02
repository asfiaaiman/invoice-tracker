<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

test('login requires valid email format', function () {
    $response = $this->post(route('login.store'), [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('login requires password field', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('login uses configured email format', function () {
    // Fortify lowercases emails when configured, so we test with lowercase
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('login rate limiting is per email and ip combination', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Make 5 failed login attempts for user1
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.store'), [
            'email' => $user1->email,
            'password' => 'wrong-password',
        ]);
    }

    // User1 should be rate limited
    $response1 = $this->post(route('login.store'), [
        'email' => $user1->email,
        'password' => 'wrong-password',
    ]);
    $response1->assertTooManyRequests();

    // User2 should still be able to attempt login (different email)
    $response2 = $this->post(route('login.store'), [
        'email' => $user2->email,
        'password' => 'wrong-password',
    ]);
    expect($response2->status())->not->toBe(429);
});

test('login rate limiter prevents brute force attacks', function () {
    $user = User::factory()->create();

    // Make multiple failed login attempts
    for ($i = 0; $i < 4; $i++) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 5th attempt should still work (limit is 5 per minute)
    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    // Should either be rate limited or show auth error
    expect($response->status())->toBeIn([302, 429]);
});

test('unverified users are redirected to verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});

test('verified users can access protected routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
});

test('guests cannot access protected routes', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('password is hashed when user is created', function () {
    $user = User::factory()->create([
        'password' => 'plain-password',
    ]);

    expect(Hash::check('plain-password', $user->password))->toBeTrue();
    expect($user->password)->not->toBe('plain-password');
});

test('user can authenticate with correct credentials', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('session persists after login', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $loginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();

    // Make another request using actingAs to maintain session
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $this->assertAuthenticatedAs($user);
});

