<?php

namespace App\Actions\Invoice;

use App\Events\InvoiceDeleted;
use App\Models\Invoice;

class DeleteInvoiceAction
{
    public function execute(Invoice $invoice): bool
    {
        $invoice->delete();

        InvoiceDeleted::dispatch($invoice);

        return true;
    }
}

