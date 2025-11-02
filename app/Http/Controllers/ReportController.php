<?php

namespace App\Http\Controllers;

use App\Actions\Report\GenerateAgencyReportAction;
use App\Models\Agency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private GenerateAgencyReportAction $generateReportAction
    ) {}

    public function index(): Response
    {
        $agencies = Agency::where('is_active', true)->get();

        return Inertia::render('Reports/Index', [
            'agencies' => $agencies,
        ]);
    }

    public function show(int|string $agency): Response
    {
        $agencyId = (int) $agency;
        $reportDTO = $this->generateReportAction->execute($agencyId);

        return Inertia::render('Reports/Show', [
            'report' => [
                'agency' => Agency::findOrFail($agencyId),
                'current_year_total' => $reportDTO->currentYearTotal,
                'last_365_days_total' => $reportDTO->last365DaysTotal,
                'vat_threshold' => $reportDTO->vatThreshold,
                'remaining_amount' => $reportDTO->remainingAmount,
                'client_structure' => $reportDTO->clientStructure,
                'warnings' => $reportDTO->warnings,
                'period_start' => $reportDTO->periodStart,
                'period_end' => $reportDTO->periodEnd,
            ],
        ]);
    }

    public function period(Request $request): Response
    {
        $agencies = Agency::where('is_active', true)->get();

        $agencyId = $request->get('agency_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $invoices = collect();
        $totalAmount = 0;
        $clientSummary = collect();

        if ($agencyId && $startDate && $endDate) {
            $invoices = \App\Models\Invoice::with(['client', 'agency'])
                ->where('agency_id', $agencyId)
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->orderBy('issue_date', 'desc')
                ->get();

            $totalAmount = $invoices->sum('total');

            $clientSummary = $invoices->groupBy('client_id')->map(function ($clientInvoices, $clientId) use ($totalAmount) {
                $client = $clientInvoices->first()->client;
                $clientTotal = $clientInvoices->sum('total');

                return [
                    'client_id' => $clientId,
                    'client_name' => $client->name ?? 'Unknown',
                    'total' => round($clientTotal, 2),
                    'percentage' => $totalAmount > 0 ? round(($clientTotal / $totalAmount) * 100, 2) : 0,
                    'invoice_count' => $clientInvoices->count(),
                ];
            })->sortByDesc('total')->values();
        }

        return Inertia::render('Reports/Period', [
            'agencies' => $agencies,
            'invoices' => $invoices->toArray(),
            'totalAmount' => $totalAmount,
            'clientSummary' => $clientSummary->toArray(),
            'filters' => [
                'agency_id' => $agencyId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
