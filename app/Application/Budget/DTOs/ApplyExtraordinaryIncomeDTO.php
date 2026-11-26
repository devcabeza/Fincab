<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class ApplyExtraordinaryIncomeDTO
{
    /**
     * @param  numeric-string  $incomeAmount
     * @param  numeric-string  $accelerationPercent
     */
    public function __construct(
        public int $workspaceId,
        public int $budgetPeriodId,
        public string $incomeTitle,
        public string $incomeAmount,
        public string $accelerationPercent = '60.00',
    ) {}
}
