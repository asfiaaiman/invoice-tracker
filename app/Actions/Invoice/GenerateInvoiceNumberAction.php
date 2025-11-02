<?php

namespace App\Actions\Invoice;

use App\Models\Agency;
use Illuminate\Support\Carbon;

class GenerateInvoiceNumberAction
{
    public function execute(int $agencyId): string
    {
        $agency = Agency::findOrFail($agencyId);
        $year = Carbon::now()->year;
        $lastNumber = $this->getLastInvoiceNumber($agencyId, $year);
        $nextNumber = $lastNumber + 1;

        $prefix = $agency->invoice_number_prefix ?? 'INV';

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    private function getLastInvoiceNumber(int $agencyId, int $year): int
    {
        $agency = Agency::findOrFail($agencyId);
        $prefix = $agency->invoice_number_prefix ?? 'INV';
        
        $invoices = \App\Models\Invoice::withTrashed()
            ->where('agency_id', $agencyId)
            ->whereYear('issue_date', $year)
            ->where('invoice_number', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $maxNumber = 0;
        foreach ($invoices as $invoice) {
            $parts = explode('-', $invoice->invoice_number);
            $number = (int) end($parts);
            if ($number > $maxNumber) {
                $maxNumber = $number;
            }
        }

        return $maxNumber;
    }
}

