<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\DeleteInvoiceAction;
use App\Actions\Invoice\GenerateInvoiceNumberAction;
use App\Actions\Invoice\UpdateInvoiceAction;
use App\DTOs\InvoiceDTO;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private GenerateInvoiceNumberAction $generateInvoiceNumberAction,
        private CreateInvoiceAction $createInvoiceAction,
        private UpdateInvoiceAction $updateInvoiceAction,
        private DeleteInvoiceAction $deleteInvoiceAction
    ) {}

    public function index(Request $request): Response
    {
        $query = Invoice::with(['agency', 'client'])
            ->whereNull('deleted_at');

        if ($request->has('agency_id') && $request->agency_id) {
            $query->where('agency_id', $request->agency_id);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('issue_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('issue_date', '<=', $request->end_date);
        }

        $invoices = $query->latest('issue_date')->paginate(20)->withQueryString();
        $agencies = \App\Models\Agency::where('is_active', true)->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'agencies' => $agencies,
            'filters' => $request->only(['agency_id', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function create(): Response
    {
        $agencies = \App\Models\Agency::where('is_active', true)->get();

        return Inertia::render('Invoices/Create', [
            'agencies' => $agencies,
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumberAction->execute($data['agency_id']);
            }

            $dto = InvoiceDTO::fromArray($data);
            $invoice = $this->createInvoiceAction->execute($dto);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create invoice: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load(['agency', 'client', 'items.product']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        $agencies = \App\Models\Agency::where('is_active', true)->get();
        $invoice->load(['agency', 'client', 'items.product']);

        $clients = \App\Models\Client::whereHas('agencies', function ($q) use ($invoice) {
            $q->where('agencies.id', $invoice->agency_id);
        })->get();

        $products = \App\Models\Product::whereHas('agencies', function ($q) use ($invoice) {
            $q->where('agencies.id', $invoice->agency_id);
        })->get();

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'agencies' => $agencies,
            'clients' => $clients,
            'products' => $products,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        try {
            $dto = InvoiceDTO::fromArray($request->validated());
            $this->updateInvoiceAction->execute($invoice, $dto);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update invoice: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Invoice $invoice)
    {
        try {
            $this->deleteInvoiceAction->execute($invoice);

            return redirect()->route('invoices.index')
                ->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['agency', 'client', 'items.product']);

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
            ->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
