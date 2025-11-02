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
        $lastInvoice = \App\Models\Invoice::where('agency_id', $agencyId)
            ->whereYear('issue_date', $year)
            ->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastInvoice) {
            return 0;
        }

        $parts = explode('-', $lastInvoice->invoice_number);
        $number = (int) end($parts);

        return $number;
    }
}

