<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('password reset request requires valid email', function () {
    $response = $this->post(route('password.email'), [
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password reset request sends notification to existing user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset request handles nonexistent email', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    // Fortify shows an error by default, but this is configurable
    // The important part is that no notification is sent
    Notification::assertNothingSent();
});

test('password reset requires matching confirmation', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');

        return true;
    });
});

test('password reset requires minimum password length', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');

        return true;
    });
});

test('password reset updates user password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
    ]);

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Old password should not work
        expect(Hash::check('oldpassword', $user->fresh()->password))->toBeFalse();

        // New password should work
        expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('password reset token can only be used once', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Use token first time
        $response1 = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response1->assertSessionHasNoErrors();

        // Try to use same token again
        $response2 = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'anotherpassword',
            'password_confirmation' => 'anotherpassword',
        ]);

        $response2->assertSessionHasErrors('email');

        return true;
    });
});

test('password reset token expires after configured time', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Simulate expired token by traveling to the future
        $this->travel(70)->minutes();

        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');

        return true;
    });
});

test('password reset requires valid email for token', function () {
    Notification::fake();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $otherUser) {
        // Try to use token with wrong email
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $otherUser->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');

        return true;
    });
});

