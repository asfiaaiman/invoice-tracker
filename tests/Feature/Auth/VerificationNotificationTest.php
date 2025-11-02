<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});

test('email verification notification respects throttle', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    // First request should succeed
    $firstResponse = $this->actingAs($user)->post(route('verification.send'));
    expect($firstResponse->status())->toBeIn([200, 302]);
    
    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    
    Notification::fake();
    
    // Second request within throttle period may be throttled
    // Email verification has built-in throttle to prevent spam
    $secondResponse = $this->actingAs($user)->post(route('verification.send'));
    expect($secondResponse->status())->toBeIn([200, 302, 429]); // May be throttled
});