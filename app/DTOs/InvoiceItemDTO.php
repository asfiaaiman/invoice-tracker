<?php

namespace App\DTOs;

class InvoiceItemDTO
{
    public function __construct(
        public readonly ?int $productId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: isset($data['product_id']) && $data['product_id'] !== '' ? (int) $data['product_id'] : null,
            quantity: (float) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
            description: $data['description'] ?? null,
        );
    }
}

