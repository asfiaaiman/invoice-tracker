<?php

namespace App\Http\Controllers;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\UpdateClientAction;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(): Response
    {
        $clients = Client::with('agencies')
            ->latest()
            ->paginate(20);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function create(): Response
    {
        $agencies = \App\Models\Agency::where('is_active', true)->get();

        return Inertia::render('Clients/Create', [
            'agencies' => $agencies,
        ]);
    }

    public function store(StoreClientRequest $request, CreateClientAction $action)
    {
        try {
            $action->execute(
                $request->except('agency_ids'),
                $request->agency_ids
            );

            return redirect()->route('clients.index')
                ->with('success', 'Client created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create client: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Client $client): Response
    {
        $client->load(['agencies', 'invoices.agency']);

        return Inertia::render('Clients/Show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client): Response
    {
        $agencies = \App\Models\Agency::where('is_active', true)->get();
        $client->load('agencies');

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'agencies' => $agencies,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClientAction $action)
    {
        try {
            $action->execute(
                $client,
                $request->except('agency_ids'),
                $request->agency_ids
            );

            return redirect()->route('clients.index')
                ->with('success', 'Client updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update client: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();

            return redirect()->route('clients.index')
                ->with('success', 'Client deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete client: ' . $e->getMessage());
        }
    }
}
