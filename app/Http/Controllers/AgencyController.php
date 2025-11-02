<?php

namespace App\Http\Controllers;

use App\Actions\Agency\CreateAgencyAction;
use App\Actions\Agency\UpdateAgencyAction;
use App\Http\Requests\StoreAgencyRequest;
use App\Http\Requests\UpdateAgencyRequest;
use App\Models\Agency;
use Inertia\Inertia;
use Inertia\Response;

class AgencyController extends Controller
{
    public function index(): Response
    {
        $agencies = Agency::latest()->paginate(20);

        return Inertia::render('Agencies/Index', [
            'agencies' => $agencies,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Agencies/Create');
    }

    public function store(StoreAgencyRequest $request, CreateAgencyAction $action)
    {
        try {
            $action->execute($request->validated());

            return redirect()->route('agencies.index')
                ->with('success', 'Agency created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create agency: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Agency $agency): Response
    {
        $agency->load(['clients', 'products', 'invoices']);

        return Inertia::render('Agencies/Show', [
            'agency' => $agency,
        ]);
    }

    public function edit(Agency $agency): Response
    {
        return Inertia::render('Agencies/Edit', [
            'agency' => $agency,
        ]);
    }

    public function update(UpdateAgencyRequest $request, Agency $agency, UpdateAgencyAction $action)
    {
        try {
            $action->execute($agency, $request->validated());

            return redirect()->route('agencies.index')
                ->with('success', 'Agency updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update agency: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Agency $agency)
    {
        try {
            $agency->delete();

            return redirect()->route('agencies.index')
                ->with('success', 'Agency deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete agency: ' . $e->getMessage());
        }
    }
}
