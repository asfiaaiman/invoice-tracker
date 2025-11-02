<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('unverified user receives verification notification after registration', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification link expires after configured time', function () {
    $user = User::factory()->unverified()->create();

    // Create an expired link (negative minutes)
    $expiredUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinutes(10),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    Event::fake();

    $response = $this->actingAs($user)->get($expiredUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification link signature must be valid', function () {
    $user = User::factory()->unverified()->create();

    // Create a link with tampered signature
    $tamperedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    ) . '&tampered=value';

    Event::fake();

    $response = $this->actingAs($user)->get($tamperedUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification requires user to be logged in', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    Event::fake();

    // Try to verify without being logged in
    $response = $this->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    $response->assertRedirect(route('login'));
});

test('verification link can only be used by the correct user', function () {
    $user1 = User::factory()->unverified()->create();
    $user2 = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user1->id, 'hash' => sha1($user1->email)]
    );

    Event::fake();

    // Try to verify user1's email while logged in as user2
    $response = $this->actingAs($user2)->get($verificationUrl);

    Event::assertNotDispatched(Verified::class);
    expect($user1->fresh()->hasVerifiedEmail())->toBeFalse();
    expect($user2->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification notification is sent when requested', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verified user cannot verify email again', function () {
    $user = User::factory()->create(); // Already verified

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl);

    // Event should not be dispatched again for already verified user
    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email verification updates verified at timestamp', function () {
    $user = User::factory()->unverified()->create();
    
    expect($user->email_verified_at)->toBeNull();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

