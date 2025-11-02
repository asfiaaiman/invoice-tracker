<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class InvoiceService
{
    public function generateInvoiceNumber(int $agencyId): string
    {
        $year = Carbon::now()->year;
        $lastNumber = $this->getLastInvoiceNumber($agencyId, $year);
        $nextNumber = $lastNumber + 1;

        $prefix = Setting::get('invoice_prefix', 'INV', $agencyId);

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    protected function getLastInvoiceNumber(int $agencyId, int $year): int
    {
        $lastInvoice = Invoice::where('agency_id', $agencyId)
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

    public function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $taxRate = 0.20; // 20% VAT in Serbia
        $taxAmount = 0;
        $total = 0;

        foreach ($items as $item) {
            $itemTotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $subtotal += $itemTotal;
        }

        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    public function createInvoice(array $data): Invoice
    {
        $items = $data['items'];
        $totals = $this->calculateTotals($items);

        $invoice = Invoice::create([
            'agency_id' => $data['agency_id'],
            'client_id' => $data['client_id'],
            'invoice_number' => $data['invoice_number'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null,
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($items as $index => $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => (float) $item['quantity'] * (float) $item['unit_price'],
                'sort_order' => $index,
            ]);
        }

        return $invoice->load(['agency', 'client', 'items.product']);
    }

    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        $items = $data['items'];
        $totals = $this->calculateTotals($items);

        $invoice->update([
            'agency_id' => $data['agency_id'],
            'client_id' => $data['client_id'],
            'invoice_number' => $data['invoice_number'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null,
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
            'notes' => $data['notes'] ?? null,
        ]);

        $invoice->items()->delete();

        foreach ($items as $index => $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => (float) $item['quantity'] * (float) $item['unit_price'],
                'sort_order' => $index,
            ]);
        }

        return $invoice->load(['agency', 'client', 'items.product']);
    }
}

