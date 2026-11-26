<?php

declare(strict_types=1);

namespace App\Domain\Budget\Services;

final class CashFlowCalculator
{
    /**
     * @param  numeric-string  $totalCollectedIncome
     * @param  numeric-string  $paidExpenses
     * @param  numeric-string  $pendingExpenses
     * @param  numeric-string  $pendingQ1Expenses
     * @param  numeric-string  $pendingQ2Expenses
     * @return array{
     *     totalCollectedIncome: numeric-string,
     *     totalCommittedExpenses: numeric-string,
     *     realSavings: numeric-string,
     *     liquidNeededQ1: numeric-string,
     *     liquidNeededQ2: numeric-string,
     *     isLeisureBlocked: bool,
     *     projectedSavings: numeric-string
     * }
     */
    public function calculate(
        string $totalCollectedIncome,
        string $paidExpenses,
        string $pendingExpenses,
        string $pendingQ1Expenses,
        string $pendingQ2Expenses,
        bool $hasPendingQ1Debt,
        int $dayOfMonth,
    ): array {
        $collectedIncome = bcadd($totalCollectedIncome, '0', 8);
        $paid = bcadd($paidExpenses, '0', 8);
        $pending = bcadd($pendingExpenses, '0', 8);

        $totalCommitted = bcadd($paid, $pending, 8);
        $realSavings = bcsub($collectedIncome, $paid, 8);
        $projectedSavings = bcsub($collectedIncome, $totalCommitted, 8);

        $liquidQ1 = bcadd($pendingQ1Expenses, '0', 8);
        $liquidQ2 = bcadd($pendingQ2Expenses, '0', 8);

        $isLeisureBlocked = $dayOfMonth > 15 && $hasPendingQ1Debt;

        return [
            'totalCollectedIncome' => $collectedIncome,
            'totalCommittedExpenses' => $totalCommitted,
            'realSavings' => $realSavings,
            'liquidNeededQ1' => $liquidQ1,
            'liquidNeededQ2' => $liquidQ2,
            'isLeisureBlocked' => $isLeisureBlocked,
            'projectedSavings' => $projectedSavings,
        ];
    }
}
