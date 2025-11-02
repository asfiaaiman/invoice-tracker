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
        
        // Update invoice prefix if provided
        if (array_key_exists('invoice_number_prefix', $settings)) {
            $agency = \App\Models\Agency::findOrFail($agencyId);
            // If empty string, reset to default 'INV'; otherwise use the provided value
            $inputPrefix = $settings['invoice_number_prefix'];
            $prefix = (!empty($inputPrefix) && $inputPrefix !== null && trim($inputPrefix) !== '')
                ? strtoupper(trim($inputPrefix))
                : 'INV';
            
            $agency->update([
                'invoice_number_prefix' => $prefix,
            ]);
        }
    }
}

