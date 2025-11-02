<?php

namespace App\DTOs;

class InvoiceDTO
{
    public function __construct(
        public readonly int $agencyId,
        public readonly int $clientId,
        public readonly string $invoiceNumber,
        public readonly string $issueDate,
        public readonly ?string $dueDate = null,
        public readonly ?string $notes = null,
        public readonly array $items = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agencyId: $data['agency_id'],
            clientId: $data['client_id'],
            invoiceNumber: $data['invoice_number'],
            issueDate: $data['issue_date'],
            dueDate: $data['due_date'] ?? null,
            notes: $data['notes'] ?? null,
            items: $data['items'] ?? [],
        );
    }
}

