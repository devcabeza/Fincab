<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class CashFlowSummaryDTO
{
    public function __construct(
        public string $totalCollectedIncome,
        public string $totalCommittedExpenses,
        public string $realSavings,
        public string $liquidNeededQ1,
        public string $liquidNeededQ2,
        public bool $isLeisureBlocked,
        public ?string $projectedSavings = null,
        public ?string $paidExpenses = null,
    ) {}
}
