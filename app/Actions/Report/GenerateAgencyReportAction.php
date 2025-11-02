<?php

namespace App\Actions\Report;

use App\DTOs\ReportDTO;
use App\Models\Agency;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class GenerateAgencyReportAction
{
    public function execute(int $agencyId): ReportDTO
    {
        $agency = Agency::findOrFail($agencyId);

        $currentYearStart = Carbon::now()->startOfYear();
        $last365DaysStart = Carbon::now()->subDays(365);

        $currentYearInvoices = Invoice::where('agency_id', $agencyId)
            ->where('issue_date', '>=', $currentYearStart)
            ->whereNull('deleted_at')
            ->get();

        $last365DaysInvoices = Invoice::with('client')
            ->where('agency_id', $agencyId)
            ->where('issue_date', '>=', $last365DaysStart)
            ->whereNull('deleted_at')
            ->get();

        $currentYearTotal = $currentYearInvoices->sum('total');
        $last365DaysTotal = $last365DaysInvoices->sum('total');

        $clientStructure = $this->calculateClientStructure($last365DaysInvoices);

        $vatThreshold = (float) Setting::get('pdv_limit', '6000000', $agencyId);
        $minClients = (int) Setting::get('min_clients_per_year', '5', $agencyId);
        $maxClientShare = (float) Setting::get('client_max_share_percent', '70', $agencyId) / 100;

        $warnings = $this->validateBusinessRules($clientStructure, $last365DaysTotal, $minClients, $maxClientShare);

        return new ReportDTO(
            currentYearTotal: round($currentYearTotal, 2),
            last365DaysTotal: round($last365DaysTotal, 2),
            vatThreshold: $vatThreshold,
            remainingAmount: max(0, $vatThreshold - $last365DaysTotal),
            clientStructure: $clientStructure,
            warnings: $warnings,
            periodStart: $last365DaysStart->format('Y-m-d'),
            periodEnd: Carbon::now()->format('Y-m-d'),
        );
    }

    private function calculateClientStructure($invoices): array
    {
        $clients = [];

        foreach ($invoices as $invoice) {
            $clientId = $invoice->client_id;

            if (!$invoice->client) {
                continue;
            }

            if (!isset($clients[$clientId])) {
                $clients[$clientId] = [
                    'client_id' => $clientId,
                    'client_name' => $invoice->client->name ?? 'Unknown Client',
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

    private function validateBusinessRules(array $clientStructure, float $totalAmount, int $minClients, float $maxClientShare): array
    {
        $warnings = [];

        $uniqueClients = count($clientStructure);

        if ($uniqueClients < $minClients) {
            $warnings[] = [
                'type' => 'min_clients',
                'message' => sprintf(
                    'Agency has only %d client(s) with invoices in the last 365 days. Minimum required is %d.',
                    $uniqueClients,
                    $minClients
                ),
                'severity' => 'error',
            ];
        }

        foreach ($clientStructure as $client) {
            if ($client['percentage'] > ($maxClientShare * 100)) {
                $warnings[] = [
                    'type' => 'client_share',
                    'message' => sprintf(
                        'Client "%s" represents %.2f%% of turnover (maximum allowed is %.2f%%).',
                        $client['client_name'],
                        $client['percentage'],
                        $maxClientShare * 100
                    ),
                    'severity' => 'error',
                    'client_id' => $client['client_id'],
                ];
            }
        }

        return $warnings;
    }
}

