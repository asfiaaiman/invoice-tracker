<?php

namespace App\DTOs;

class ReportDTO
{
    public function __construct(
        public readonly float $currentYearTotal,
        public readonly float $last365DaysTotal,
        public readonly float $vatThreshold,
        public readonly float $remainingAmount,
        public readonly array $clientStructure,
        public readonly array $warnings,
        public readonly string $periodStart,
        public readonly string $periodEnd,
    ) {}
}

