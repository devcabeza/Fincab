<?php

declare(strict_types=1);

namespace App\Domain\Budget\Services;

use App\Domain\Budget\Enums\DebtStrategy;

final class BudgetWaterfallCalculator
{
    public function __construct(
        private readonly DebtPrioritizer $prioritizer = new DebtPrioritizer,
    ) {}

    /**
     * @param  numeric-string  $totalIncome
     * @param  numeric-string  $essentialsExpenses
     * @param  list<array{id: int, current_balance: numeric-string, annual_interest_rate: numeric-string, minimum_payment: numeric-string}>  $debts
     * @param  numeric-string  $extraDebtPaymentPercent
     * @param  array<string, numeric-string>  $bucketPercentages
     * @return array{
     *     totalIncome: numeric-string,
     *     essentialsAmount: numeric-string,
     *     minimumDebtsAmount: numeric-string,
     *     surplusAfterEssentialsAndMinimums: numeric-string,
     *     acceleratorAmount: numeric-string,
     *     acceleratorDebtId: ?int,
     *     remainingSurplus: numeric-string,
     *     bucketLimits: array<string, numeric-string>
     * }
     */
    public function calculate(
        string $totalIncome,
        string $essentialsExpenses,
        array $debts,
        string $extraDebtPaymentPercent,
        DebtStrategy $strategy,
        array $bucketPercentages,
    ): array {
        $totalIncome = bcadd($totalIncome, '0', 8);
        $essentialsAmount = bcadd($essentialsExpenses, '0', 8);

        $minimumDebtsAmount = '0.00000000';
        $activeDebts = [];

        foreach ($debts as $debt) {
            $minPay = bcadd($debt['minimum_payment'], '0', 8);
            $minimumDebtsAmount = bcadd($minimumDebtsAmount, $minPay, 8);
            if (bccomp($debt['current_balance'], '0', 8) > 0) {
                $activeDebts[] = $debt;
            }
        }

        $mandatoryTotal = bcadd($essentialsAmount, $minimumDebtsAmount, 8);

        if (bccomp($totalIncome, $mandatoryTotal, 8) <= 0) {
            $surplus = '0.00000000';
            $acceleratorAmount = '0.00000000';
            $acceleratorDebtId = null;
            $remainingSurplus = '0.00000000';

            $bucketLimits = [];
            foreach ($bucketPercentages as $key => $pct) {
                $bucketLimits[$key] = '0.00000000';
            }

            return [
                'totalIncome' => $totalIncome,
                'essentialsAmount' => $essentialsAmount,
                'minimumDebtsAmount' => $minimumDebtsAmount,
                'surplusAfterEssentialsAndMinimums' => $surplus,
                'acceleratorAmount' => $acceleratorAmount,
                'acceleratorDebtId' => $acceleratorDebtId,
                'remainingSurplus' => $remainingSurplus,
                'bucketLimits' => $bucketLimits,
            ];
        }

        $surplus = bcsub($totalIncome, $mandatoryTotal, 8);
        $acceleratorAmount = '0.00000000';
        $acceleratorDebtId = null;

        if (count($activeDebts) > 0 && bccomp($extraDebtPaymentPercent, '0', 8) > 0) {
            $prioritized = $this->prioritizer->prioritize($activeDebts, $strategy);
            $topDebt = $prioritized[0];
            $acceleratorDebtId = $topDebt['id'];

            $extraPercent = bcadd($extraDebtPaymentPercent, '0', 8);
            $acceleratorAmount = bcdiv(bcmul($surplus, $extraPercent, 8), '100', 8);
        }

        $remainingSurplus = bcsub($surplus, $acceleratorAmount, 8);

        $bucketLimits = [];
        foreach ($bucketPercentages as $key => $pct) {
            $percent = bcadd($pct, '0', 8);
            $bucketLimits[$key] = bcdiv(bcmul($remainingSurplus, $percent, 8), '100', 8);
        }

        return [
            'totalIncome' => $totalIncome,
            'essentialsAmount' => $essentialsAmount,
            'minimumDebtsAmount' => $minimumDebtsAmount,
            'surplusAfterEssentialsAndMinimums' => $surplus,
            'acceleratorAmount' => $acceleratorAmount,
            'acceleratorDebtId' => $acceleratorDebtId,
            'remainingSurplus' => $remainingSurplus,
            'bucketLimits' => $bucketLimits,
        ];
    }
}
