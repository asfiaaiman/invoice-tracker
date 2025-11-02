<?php

namespace App\Actions\Settings;

use App\Models\Agency;
use App\Models\Setting;

class GetApplicationSettingsAction
{
    private const DEFAULT_PDV_LIMIT = '6000000';
    private const DEFAULT_CLIENT_MAX_SHARE = '70';
    private const DEFAULT_MIN_CLIENTS = '5';

    public function execute(): array
    {
        $agencies = Agency::where('is_active', true)->get();

        $defaultSettings = [
            'pdv_limit' => self::DEFAULT_PDV_LIMIT,
            'client_max_share_percent' => self::DEFAULT_CLIENT_MAX_SHARE,
            'min_clients_per_year' => self::DEFAULT_MIN_CLIENTS,
        ];

        $settings = [];
        foreach ($agencies as $agency) {
            $settings[$agency->id] = [
                'pdv_limit' => Setting::get('pdv_limit', self::DEFAULT_PDV_LIMIT, $agency->id),
                'client_max_share_percent' => Setting::get('client_max_share_percent', self::DEFAULT_CLIENT_MAX_SHARE, $agency->id),
                'min_clients_per_year' => Setting::get('min_clients_per_year', self::DEFAULT_MIN_CLIENTS, $agency->id),
            ];
        }

        return [
            'agencies' => $agencies,
            'settings' => $settings,
            'defaultSettings' => $defaultSettings,
        ];
    }
}

