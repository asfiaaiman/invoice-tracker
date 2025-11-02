<?php

namespace App\Actions\Invoice;

use App\DTOs\InvoiceItemDTO;

class CalculateInvoiceTotalsAction
{
    private const VAT_RATE = 0.20; // 20% VAT in Serbia

    public function execute(array $items): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $itemDTO = $item instanceof InvoiceItemDTO ? $item : InvoiceItemDTO::fromArray($item);
            $itemTotal = $itemDTO->quantity * $itemDTO->unitPrice;
            $subtotal += $itemTotal;
        }

        $taxAmount = $subtotal * self::VAT_RATE;
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }
}

