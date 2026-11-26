<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class LiquidityMetricsDTO
{
    /**
     * @param  numeric-string  $salary
     * @param  numeric-string  $sideProjects
     * @param  numeric-string  $investments
     * @param  numeric-string  $otherIncomes
     * @param  numeric-string  $currentIncomesTotal
     * @param  numeric-string  $previousPeriodSavings
     * @param  numeric-string  $totalInflows
     * @param  numeric-string  $totalExpenses
     * @param  numeric-string  $paidExpenses
     * @param  numeric-string  $pendingExpenses
     * @param  numeric-string  $netLiquidity
     */
    public function __construct(
        public string $salary,
        public string $sideProjects,
        public string $investments,
        public string $otherIncomes,
        public string $currentIncomesTotal,
        public string $previousPeriodSavings,
        public string $totalInflows,
        public string $totalExpenses,
        public string $paidExpenses,
        public string $pendingExpenses,
        public string $netLiquidity,
        public ?string $previousPeriodLabel = null,
    ) {}
}
