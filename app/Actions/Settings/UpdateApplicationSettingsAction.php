<?php

namespace App\Actions\Settings;

use App\Models\Setting;

class UpdateApplicationSettingsAction
{
    public function execute(int $agencyId, array $settings): void
    {
        Setting::set('pdv_limit', $settings['pdv_limit'], $agencyId);
        Setting::set('client_max_share_percent', $settings['client_max_share_percent'], $agencyId);
        Setting::set('min_clients_per_year', $settings['min_clients_per_year'], $agencyId);
    }
}

