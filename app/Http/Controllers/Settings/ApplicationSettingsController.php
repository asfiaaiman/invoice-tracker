<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\GetApplicationSettingsAction;
use App\Actions\Settings\UpdateApplicationSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationSettingsRequest;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationSettingsController extends Controller
{
    public function __construct(
        private GetApplicationSettingsAction $getSettingsAction,
        private UpdateApplicationSettingsAction $updateSettingsAction
    ) {}

    public function index(): Response
    {
        $data = $this->getSettingsAction->execute();

        return Inertia::render('settings/Application', $data);
    }

    public function update(UpdateApplicationSettingsRequest $request)
    {
        try {
            $settingsData = $request->only(['pdv_limit', 'client_max_share_percent', 'min_clients_per_year', 'invoice_number_prefix']);
            
            // Ensure invoice_number_prefix is included even if empty
            if ($request->has('invoice_number_prefix')) {
                $settingsData['invoice_number_prefix'] = $request->input('invoice_number_prefix');
            }
            
            $this->updateSettingsAction->execute(
                $request->agency_id,
                $settingsData
            );

            return redirect()->route('settings.application')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }
}
