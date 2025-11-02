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
        $settings = Setting::where('agency_id', $agency->id)
            ->pluck('value', 'key')
            ->toArray();

        return Inertia::render('Agencies/Settings', [
            'agency' => $agency,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, Agency $agency)
    {
        try {
            $validated = $request->validate([
                'settings' => ['nullable', 'array'],
                'settings.*' => ['nullable', 'string'],
            ]);

            if (isset($validated['settings'])) {
                foreach ($validated['settings'] as $key => $value) {
                    Setting::set($key, $value, $agency->id);
                }
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
