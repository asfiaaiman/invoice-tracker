<?php

use App\Actions\Settings\GetApplicationSettingsAction;
use App\Models\Agency;
use App\Models\Setting;

test('get application settings returns all active agencies', function () {
    $action = new GetApplicationSettingsAction();

    Agency::factory()->count(3)->create(['is_active' => true]);
    Agency::factory()->create(['is_active' => false]);

    $result = $action->execute();

    expect($result['agencies'])->toHaveCount(3);
    expect(collect($result['agencies'])->pluck('is_active')->unique())->toContain(true);
    expect(collect($result['agencies'])->pluck('is_active'))->not->toContain(false);
});

test('get application settings returns default values when no settings exist', function () {
    $action = new GetApplicationSettingsAction();

    $agency = Agency::factory()->create(['is_active' => true, 'invoice_number_prefix' => 'INV']);

    $result = $action->execute();

    expect($result['defaultSettings']['pdv_limit'])->toBe('6000000');
    expect($result['defaultSettings']['client_max_share_percent'])->toBe('70');
    expect($result['defaultSettings']['min_clients_per_year'])->toBe('5');
    expect($result['defaultSettings']['invoice_number_prefix'])->toBe('INV');
});

test('get application settings returns custom settings when they exist', function () {
    $action = new GetApplicationSettingsAction();

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'CUSTOM',
    ]);

    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '8000000',
    ]);

    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'client_max_share_percent',
        'value' => '80',
    ]);

    $result = $action->execute();

    expect($result['settings'][$agency->id]['pdv_limit'])->toBe('8000000');
    expect($result['settings'][$agency->id]['client_max_share_percent'])->toBe('80');
    expect($result['settings'][$agency->id]['min_clients_per_year'])->toBe('5');
    expect($result['settings'][$agency->id]['invoice_number_prefix'])->toBe('CUSTOM');
});

test('get application settings uses default invoice prefix when agency has none', function () {
    $action = new GetApplicationSettingsAction();

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'INV',
    ]);

    $result = $action->execute();

    expect($result['settings'][$agency->id]['invoice_number_prefix'])->toBe('INV');
});

test('get application settings returns settings for multiple agencies', function () {
    $action = new GetApplicationSettingsAction();

    $agency1 = Agency::factory()->create(['is_active' => true, 'invoice_number_prefix' => 'AG1']);
    $agency2 = Agency::factory()->create(['is_active' => true, 'invoice_number_prefix' => 'AG2']);

    Setting::create([
        'agency_id' => $agency1->id,
        'key' => 'pdv_limit',
        'value' => '7000000',
    ]);

    Setting::create([
        'agency_id' => $agency2->id,
        'key' => 'pdv_limit',
        'value' => '9000000',
    ]);

    $result = $action->execute();

    expect($result['settings'][$agency1->id]['pdv_limit'])->toBe('7000000');
    expect($result['settings'][$agency1->id]['invoice_number_prefix'])->toBe('AG1');
    expect($result['settings'][$agency2->id]['pdv_limit'])->toBe('9000000');
    expect($result['settings'][$agency2->id]['invoice_number_prefix'])->toBe('AG2');
});

