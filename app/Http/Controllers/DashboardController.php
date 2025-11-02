<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $last365DaysStart = $now->copy()->subDays(365);

        $totalAgencies = Agency::where('is_active', true)->count();
        $totalClients = Client::count();
        $totalProducts = Product::count();

        $totalInvoices = Invoice::whereNull('deleted_at')->count();
        $thisMonthInvoices = Invoice::whereNull('deleted_at')
            ->where('issue_date', '>=', $startOfMonth)
            ->count();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();
        $lastMonthInvoices = Invoice::whereNull('deleted_at')
            ->whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $totalRevenue = Invoice::whereNull('deleted_at')->sum('total');
        $thisMonthRevenue = Invoice::whereNull('deleted_at')
            ->where('issue_date', '>=', $startOfMonth)
            ->sum('total');
        $lastMonthRevenue = Invoice::whereNull('deleted_at')
            ->whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('total');

        $thisYearRevenue = Invoice::whereNull('deleted_at')
            ->where('issue_date', '>=', $startOfYear)
            ->sum('total');

        $last365DaysRevenue = Invoice::whereNull('deleted_at')
            ->where('issue_date', '>=', $last365DaysStart)
            ->sum('total');

        $recentInvoices = Invoice::with(['agency', 'client'])
            ->whereNull('deleted_at')
            ->orderBy('issue_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'agency_name' => $invoice->agency->name,
                'client_name' => $invoice->client->name,
                'total' => round($invoice->total, 2),
                'issue_date' => $invoice->issue_date->format('Y-m-d'),
            ]);

        $monthlyRevenue = Invoice::whereNull('deleted_at')
            ->where('issue_date', '>=', $startOfYear)
            ->get()
            ->groupBy(fn($invoice) => $invoice->issue_date->month)
            ->map(fn($invoices, $month) => [
                'month' => (int) $month,
                'revenue' => round($invoices->sum('total'), 2),
            ])
            ->sortBy('month')
            ->values();

        $invoiceTrend = $lastMonthInvoices > 0
            ? round((($thisMonthInvoices - $lastMonthInvoices) / $lastMonthInvoices) * 100, 1)
            : ($thisMonthInvoices > 0 ? 100 : 0);

        $revenueTrend = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($thisMonthRevenue > 0 ? 100 : 0);

        $agencyStats = Agency::where('is_active', true)->withCount('invoices')->get()->map(function ($agency) use ($now, $startOfYear, $last365DaysStart) {
            $agencyCurrentYear = Invoice::where('agency_id', $agency->id)
                ->whereNull('deleted_at')
                ->where('issue_date', '>=', $startOfYear)
                ->sum('total');

            $agencyLast365 = Invoice::where('agency_id', $agency->id)
                ->whereNull('deleted_at')
                ->where('issue_date', '>=', $last365DaysStart)
                ->sum('total');

            return [
                'id' => $agency->id,
                'name' => $agency->name,
                'invoice_count' => $agency->invoices_count,
                'current_year_revenue' => round($agencyCurrentYear, 2),
                'last_365_days_revenue' => round($agencyLast365, 2),
            ];
        })->sortByDesc('last_365_days_revenue')->values();

        return Inertia::render('Dashboard', [
            'stats' => [
                'agencies' => [
                    'total' => $totalAgencies,
                    'href' => '/agencies',
                ],
                'clients' => [
                    'total' => $totalClients,
                    'href' => '/clients',
                ],
                'products' => [
                    'total' => $totalProducts,
                    'href' => '/products',
                ],
                'invoices' => [
                    'total' => $totalInvoices,
                    'thisMonth' => $thisMonthInvoices,
                    'trend' => $invoiceTrend,
                    'href' => '/invoices',
                ],
                'revenue' => [
                    'total' => round($totalRevenue, 2),
                    'thisMonth' => round($thisMonthRevenue, 2),
                    'thisYear' => round($thisYearRevenue, 2),
                    'last365Days' => round($last365DaysRevenue, 2),
                    'trend' => $revenueTrend,
                ],
            ],
            'recentInvoices' => $recentInvoices,
            'monthlyRevenue' => $monthlyRevenue,
            'agencyStats' => $agencyStats,
        ]);
    }
}
