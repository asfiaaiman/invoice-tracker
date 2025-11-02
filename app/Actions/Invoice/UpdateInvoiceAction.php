<?php

namespace App\Actions\Invoice;

use App\DTOs\InvoiceDTO;
use App\DTOs\InvoiceItemDTO;
use App\Models\Invoice;
use App\Events\InvoiceUpdated;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceAction
{
    public function __construct(
        private CalculateInvoiceTotalsAction $calculateTotalsAction
    ) {}

    public function execute(Invoice $invoice, InvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($invoice, $dto) {
            $items = array_map(fn($item) => InvoiceItemDTO::fromArray($item), $dto->items);
            $totals = $this->calculateTotalsAction->execute($items);

            $invoice->update([
                'agency_id' => $dto->agencyId,
                'client_id' => $dto->clientId,
                'invoice_number' => $dto->invoiceNumber,
                'issue_date' => $dto->issueDate,
                'due_date' => $dto->dueDate,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $dto->notes,
            ]);

            $invoice->items()->delete();

            foreach ($items as $index => $itemDTO) {
                $invoice->items()->create([
                    'product_id' => $itemDTO->productId,
                    'description' => $itemDTO->description,
                    'quantity' => $itemDTO->quantity,
                    'unit_price' => $itemDTO->unitPrice,
                    'total' => $itemDTO->quantity * $itemDTO->unitPrice,
                    'sort_order' => $index,
                ]);
            }

            $invoice->load(['agency', 'client', 'items.product']);

            InvoiceUpdated::dispatch($invoice);

            return $invoice;
        });
    }
}

