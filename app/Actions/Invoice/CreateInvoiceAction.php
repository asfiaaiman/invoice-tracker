<?php

namespace App\Actions\Invoice;

use App\DTOs\InvoiceDTO;
use App\DTOs\InvoiceItemDTO;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Events\InvoiceCreated;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    public function __construct(
        private CalculateInvoiceTotalsAction $calculateTotalsAction
    ) {}

    public function execute(InvoiceDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            $items = array_map(fn($item) => InvoiceItemDTO::fromArray($item), $dto->items);
            $totals = $this->calculateTotalsAction->execute($items);

            $invoice = Invoice::create([
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

            foreach ($items as $index => $itemDTO) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $itemDTO->productId,
                    'description' => $itemDTO->description,
                    'quantity' => $itemDTO->quantity,
                    'unit_price' => $itemDTO->unitPrice,
                    'total' => $itemDTO->quantity * $itemDTO->unitPrice,
                    'sort_order' => $index,
                ]);
            }

            $invoice->load(['agency', 'client', 'items.product']);

            InvoiceCreated::dispatch($invoice);

            return $invoice;
        });
    }
}

