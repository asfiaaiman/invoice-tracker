<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

class ReportService
{
    private const VAT_THRESHOLD = 6000000; // 6,000,000 RSD
    private const MIN_CLIENTS = 5;
    private const MAX_CLIENT_SHARE = 0.70; // 70%

    public function getAgencyReport(int $agencyId): array
    {
        $agency = Agency::findOrFail($agencyId);
        
        $currentYearStart = Carbon::now()->startOfYear();
        $last365DaysStart = Carbon::now()->subDays(365);

        $currentYearInvoices = Invoice::where('agency_id', $agencyId)
            ->where('issue_date', '>=', $currentYearStart)
            ->whereNull('deleted_at')
            ->get();

        $last365DaysInvoices = Invoice::where('agency_id', $agencyId)
            ->where('issue_date', '>=', $last365DaysStart)
            ->whereNull('deleted_at')
            ->get();

        $currentYearTotal = $currentYearInvoices->sum('total');
        $last365DaysTotal = $last365DaysInvoices->sum('total');

        $clientStructure = $this->getClientStructure($last365DaysInvoices);

        $warnings = $this->checkBusinessRules($clientStructure, $last365DaysTotal);

        return [
            'agency' => $agency,
            'current_year_total' => round($currentYearTotal, 2),
            'last_365_days_total' => round($last365DaysTotal, 2),
            'vat_threshold' => self::VAT_THRESHOLD,
            'remaining_amount' => max(0, self::VAT_THRESHOLD - $last365DaysTotal),
            'client_structure' => $clientStructure,
            'warnings' => $warnings,
            'period_start' => $last365DaysStart->format('Y-m-d'),
            'period_end' => Carbon::now()->format('Y-m-d'),
        ];
    }

    protected function getClientStructure($invoices): array
    {
        $clients = [];

        foreach ($invoices as $invoice) {
            $clientId = $invoice->client_id;
            
            if (!isset($clients[$clientId])) {
                $clients[$clientId] = [
                    'client_id' => $clientId,
                    'client_name' => $invoice->client->name,
                    'total' => 0,
                    'count' => 0,
                ];
            }

            $clients[$clientId]['total'] += $invoice->total;
            $clients[$clientId]['count']++;
        }

        $totalAmount = array_sum(array_column($clients, 'total'));

        foreach ($clients as &$client) {
            $client['percentage'] = $totalAmount > 0 
                ? round(($client['total'] / $totalAmount) * 100, 2) 
                : 0;
            $client['total'] = round($client['total'], 2);
        }

        usort($clients, fn($a, $b) => $b['total'] <=> $a['total']);

        return array_values($clients);
    }

    protected function checkBusinessRules(array $clientStructure, float $totalAmount): array
    {
        $warnings = [];

        $uniqueClients = count($clientStructure);
        
        if ($uniqueClients < self::MIN_CLIENTS) {
            $warnings[] = [
                'type' => 'min_clients',
                'message' => sprintf(
                    'Agency has only %d client(s). Minimum required is %d.',
                    $uniqueClients,
                    self::MIN_CLIENTS
                ),
                'severity' => 'error',
            ];
        }

        foreach ($clientStructure as $client) {
            if ($client['percentage'] > (self::MAX_CLIENT_SHARE * 100)) {
                $warnings[] = [
                    'type' => 'client_share',
                    'message' => sprintf(
                        'Client "%s" represents %.2f%% of turnover (maximum allowed is %.2f%%).',
                        $client['client_name'],
                        $client['percentage'],
                        self::MAX_CLIENT_SHARE * 100
                    ),
                    'severity' => 'error',
                    'client_id' => $client['client_id'],
                ];
            }
        }

        return $warnings;
    }
}

