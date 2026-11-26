<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class ExtraordinaryIncomeResultDTO
{
    /**
     * @param  numeric-string  $acceleratedAmount
     * @param  numeric-string  $remainingIncomeAmount
     */
    public function __construct(
        public int $incomeBudgetItemId,
        public ?int $extraordinaryBudgetItemId,
        public ?int $allocatedDebtId,
        public string $acceleratedAmount,
        public string $remainingIncomeAmount,
    ) {}
}
