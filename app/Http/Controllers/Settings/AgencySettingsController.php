<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgencySettingsController extends Controller
{
    public function index(Agency $agency): Response
    {
        $defaultSettings = [
            'pdv_limit' => '6000000',
            'client_max_share_percent' => '70',
            'min_clients_per_year' => '5',
            'invoice_number_prefix' => 'INV',
        ];

        $settings = [
            'pdv_limit' => Setting::get('pdv_limit', $defaultSettings['pdv_limit'], $agency->id),
            'client_max_share_percent' => Setting::get('client_max_share_percent', $defaultSettings['client_max_share_percent'], $agency->id),
            'min_clients_per_year' => Setting::get('min_clients_per_year', $defaultSettings['min_clients_per_year'], $agency->id),
            'invoice_number_prefix' => $agency->invoice_number_prefix ?? $defaultSettings['invoice_number_prefix'],
        ];

        return Inertia::render('Agencies/Settings', [
            'agency' => $agency,
            'settings' => $settings,
            'defaultSettings' => $defaultSettings,
        ]);
    }

    public function update(Request $request, Agency $agency)
    {
        $validated = $request->validate([
            'pdv_limit' => ['required', 'numeric', 'min:0'],
            'client_max_share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_clients_per_year' => ['required', 'numeric', 'min:1'],
            'invoice_number_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_]+$/'],
        ]);

        try {
            Setting::set('pdv_limit', (string) $validated['pdv_limit'], $agency->id);
            Setting::set('client_max_share_percent', (string) $validated['client_max_share_percent'], $agency->id);
            Setting::set('min_clients_per_year', (string) $validated['min_clients_per_year'], $agency->id);

            // Update invoice prefix directly on agency model
            // If provided and not empty, use it; if empty string, reset to default 'INV'
            if (array_key_exists('invoice_number_prefix', $validated)) {
                $prefix = $validated['invoice_number_prefix'] !== '' && $validated['invoice_number_prefix'] !== null
                    ? strtoupper(trim($validated['invoice_number_prefix']))
                    : 'INV';

                $agency->update([
                    'invoice_number_prefix' => $prefix,
                ]);
            }

            return redirect()->route('agencies.settings', $agency)
                ->with('success', 'Agency settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }
}
