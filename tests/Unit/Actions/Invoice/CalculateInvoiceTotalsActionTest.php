<?php

use App\Actions\Invoice\CalculateInvoiceTotalsAction;
use App\DTOs\InvoiceItemDTO;

test('calculates invoice totals correctly', function () {
    $action = new CalculateInvoiceTotalsAction();

    $items = [
        ['product_id' => 1, 'quantity' => 10, 'unit_price' => 100, 'description' => null],
        ['product_id' => 2, 'quantity' => 5, 'unit_price' => 200, 'description' => null],
    ];

    $totals = $action->execute($items);

    expect($totals['subtotal'])->toBe(2000.0); // (10 * 100) + (5 * 200)
    expect($totals['tax_amount'])->toBe(400.0); // 20% of 2000
    expect($totals['total'])->toBe(2400.0); // 2000 + 400
});

test('handles decimal quantities and prices', function () {
    $action = new CalculateInvoiceTotalsAction();

    $items = [
        ['product_id' => 1, 'quantity' => 1.5, 'unit_price' => 100.50, 'description' => null],
    ];

    $totals = $action->execute($items);

    expect($totals['subtotal'])->toBe(150.75); // 1.5 * 100.50
    expect($totals['tax_amount'])->toBe(30.15); // 20% of 150.75
    expect($totals['total'])->toBe(180.90); // 150.75 + 30.15
});

test('works with invoice item DTOs', function () {
    $action = new CalculateInvoiceTotalsAction();

    $items = [
        InvoiceItemDTO::fromArray(['product_id' => 1, 'quantity' => 2, 'unit_price' => 50, 'description' => null]),
    ];

    $totals = $action->execute($items);

    expect($totals['subtotal'])->toBe(100.0);
    expect($totals['tax_amount'])->toBe(20.0);
    expect($totals['total'])->toBe(120.0);
});

