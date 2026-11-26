<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\CashFlowSummaryDTO;
use App\Application\Budget\DTOs\GetCashFlowSummaryDTO;
use App\Domain\Budget\Services\CashFlowCalculator;
use App\Models\BudgetPeriod;

final class GetCashFlowSummaryAction
{
    public function __construct(
        private readonly CashFlowCalculator $calculator = new CashFlowCalculator,
    ) {}

    public function execute(GetCashFlowSummaryDTO $dto): CashFlowSummaryDTO
    {
        /** @var BudgetPeriod $period */
        $period = BudgetPeriod::query()->with('items')->findOrFail($dto->budgetPeriodId);

        $totalCollectedIncome = '0.00000000';
        $paidExpenses = '0.00000000';
        $pendingExpenses = '0.00000000';
        $pendingQ1Expenses = '0.00000000';
        $pendingQ2Expenses = '0.00000000';
        $hasPendingQ1Debt = false;

        foreach ($period->items as $item) {
            $totalAmount = bcadd($this->toNumeric($item->amount_q1), $this->toNumeric($item->amount_q2), 8);

            if ($item->item_type === 'income') {
                if ($item->status === 'paid') {
                    $totalCollectedIncome = bcadd($totalCollectedIncome, $totalAmount, 8);
                }
            } elseif ($item->item_type === 'expense') {
                if ($item->status === 'paid') {
                    $paidExpenses = bcadd($paidExpenses, $totalAmount, 8);
                } elseif ($item->status === 'pending') {
                    $pendingExpenses = bcadd($pendingExpenses, $totalAmount, 8);
                    $pendingQ1Expenses = bcadd($pendingQ1Expenses, $this->toNumeric($item->amount_q1), 8);
                    $pendingQ2Expenses = bcadd($pendingQ2Expenses, $this->toNumeric($item->amount_q2), 8);

                    if ($item->debt_id !== null && bccomp($this->toNumeric($item->amount_q1), '0', 8) > 0) {
                        $hasPendingQ1Debt = true;
                    }
                }
            }
        }

        $dayOfMonth = $dto->referenceDate !== null
            ? (int) $dto->referenceDate->format('j')
            : (int) date('j');

        $result = $this->calculator->calculate(
            totalCollectedIncome: $totalCollectedIncome,
            paidExpenses: $paidExpenses,
            pendingExpenses: $pendingExpenses,
            pendingQ1Expenses: $pendingQ1Expenses,
            pendingQ2Expenses: $pendingQ2Expenses,
            hasPendingQ1Debt: $hasPendingQ1Debt,
            dayOfMonth: $dayOfMonth,
        );

        return new CashFlowSummaryDTO(
            totalCollectedIncome: $result['totalCollectedIncome'],
            totalCommittedExpenses: $result['totalCommittedExpenses'],
            realSavings: $result['realSavings'],
            liquidNeededQ1: $result['liquidNeededQ1'],
            liquidNeededQ2: $result['liquidNeededQ2'],
            isLeisureBlocked: $result['isLeisureBlocked'],
            projectedSavings: $result['projectedSavings'],
            paidExpenses: $paidExpenses,
        );
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
