<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class BudgetWaterfallResultDTO
{
    /**
     * @param  array<string, string>  $bucketLimits
     */
    public function __construct(
        public string $totalIncome,
        public string $essentialsAmount,
        public string $minimumDebtsAmount,
        public string $surplusAfterEssentialsAndMinimums,
        public string $acceleratorAmount,
        public ?int $acceleratorDebtId,
        public string $remainingSurplus,
        public array $bucketLimits,
    ) {}
}
