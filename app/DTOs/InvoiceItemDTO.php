<?php

namespace App\DTOs;

class InvoiceItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: (float) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
            description: $data['description'] ?? null,
        );
    }
}

