<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class CalculateBudgetWaterfallDTO
{
    public function __construct(
        public int $budgetPeriodId,
        public ?string $confirmedIncome = null,
    ) {}
}
