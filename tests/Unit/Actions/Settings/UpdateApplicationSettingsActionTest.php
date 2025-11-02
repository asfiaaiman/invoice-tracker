<?php

use App\Actions\Settings\UpdateApplicationSettingsAction;
use App\Models\Agency;
use App\Models\Setting;

test('update application settings saves all settings to database', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create(['is_active' => true]);

    $action->execute($agency->id, [
        'pdv_limit' => '7500000',
        'client_max_share_percent' => '85',
        'min_clients_per_year' => '7',
        'invoice_number_prefix' => 'TEST',
    ]);

    expect(Setting::where('agency_id', $agency->id)->where('key', 'pdv_limit')->first()->value)->toBe('7500000');
    expect(Setting::where('agency_id', $agency->id)->where('key', 'client_max_share_percent')->first()->value)->toBe('85');
    expect(Setting::where('agency_id', $agency->id)->where('key', 'min_clients_per_year')->first()->value)->toBe('7');
    expect($agency->fresh()->invoice_number_prefix)->toBe('TEST');
});

test('update application settings updates invoice prefix on agency', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'OLD',
    ]);

    $action->execute($agency->id, [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'NEW',
    ]);

    expect($agency->fresh()->invoice_number_prefix)->toBe('NEW');
});

test('update application settings converts invoice prefix to uppercase', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create(['is_active' => true]);

    $action->execute($agency->id, [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => 'lowercase',
    ]);

    expect($agency->fresh()->invoice_number_prefix)->toBe('LOWERCASE');
});

test('update application settings resets invoice prefix to default when empty', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'OLD',
    ]);

    $action->execute($agency->id, [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => '',
    ]);

    expect($agency->fresh()->invoice_number_prefix)->toBe('INV');
});

test('update application settings trims whitespace from invoice prefix', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create(['is_active' => true]);

    $action->execute($agency->id, [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => '  TEST  ',
    ]);

    expect($agency->fresh()->invoice_number_prefix)->toBe('TEST');
});

test('update application settings does not update invoice prefix if not provided', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'ORIGINAL',
    ]);

    $action->execute($agency->id, [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
    ]);

    expect($agency->fresh()->invoice_number_prefix)->toBe('ORIGINAL');
});

test('update application settings updates existing settings', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create(['is_active' => true]);

    Setting::create([
        'agency_id' => $agency->id,
        'key' => 'pdv_limit',
        'value' => '5000000',
    ]);

    $action->execute($agency->id, [
        'pdv_limit' => '8000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
    ]);

    expect(Setting::where('agency_id', $agency->id)->where('key', 'pdv_limit')->first()->value)->toBe('8000000');
});

test('update application settings handles null invoice prefix', function () {
    $action = new UpdateApplicationSettingsAction();

    $agency = Agency::factory()->create([
        'is_active' => true,
        'invoice_number_prefix' => 'OLD',
    ]);

    $action->execute($agency->id, [
        'pdv_limit' => '6000000',
        'client_max_share_percent' => '70',
        'min_clients_per_year' => '5',
        'invoice_number_prefix' => null,
    ]);

    expect($agency->fresh()->invoice_number_prefix)->toBe('INV');
});

