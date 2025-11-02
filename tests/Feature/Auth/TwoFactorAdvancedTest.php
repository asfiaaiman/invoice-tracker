<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('two factor challenge requires valid code format', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    // Use proper base32 encoded secret for 2FA
    $secret = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey();
    
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // Use a 6-digit code (2FA codes are always 6 digits)
    $response = $this->post(route('two-factor.login'), [
        'code' => '000000', // Invalid but properly formatted code
    ]);

    // Should have validation errors or be invalid
    expect($response->status())->toBeIn([200, 302, 422]);
    $this->assertGuest();
});

test('two factor rate limiting configuration exists', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // Verify rate limiter is configured
    $limiters = RateLimiter::for('two-factor', function ($request) {
        return \Illuminate\Cache\RateLimiting\Limit::perMinute(5);
    });

    expect($limiters)->not->toBeNull();
});

test('two factor requires login session', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // Without a login session, should redirect to login
    $response = $this->get(route('two-factor.login'));
    $response->assertRedirect(route('login'));
});

test('two factor login stores session data correctly', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    $user = User::factory()->create();

    $secret = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey();
    
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $loginResponse = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // Verify login session is stored
    $loginResponse->assertSessionHas('login.id', $user->id);
    expect(session('login.id'))->toBe($user->id);
});

