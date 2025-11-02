<?php

namespace App\Actions\Invoice;

use App\Models\Agency;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class GenerateInvoiceNumberAction
{
    public function execute(int $agencyId): string
    {
        $year = Carbon::now()->year;
        $lastNumber = $this->getLastInvoiceNumber($agencyId, $year);
        $nextNumber = $lastNumber + 1;

        $prefix = Setting::get('invoice_prefix', 'INV', $agencyId);

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

