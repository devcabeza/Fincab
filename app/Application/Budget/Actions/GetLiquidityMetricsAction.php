<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\LiquidityMetricsDTO;
use App\Models\Account;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

final class GetLiquidityMetricsAction
{
    public function execute(int $workspaceId, ?int $budgetPeriodId = null): LiquidityMetricsDTO
    {
        $period = $budgetPeriodId !== null
            ? BudgetPeriod::query()->where('workspace_id', $workspaceId)->with('items')->find($budgetPeriodId)
            : BudgetPeriod::query()->where('workspace_id', $workspaceId)->with('items')->latest('period_start')->first();

        if ($period === null) {
            return new LiquidityMetricsDTO(
                salary: '0.00000000',
                sideProjects: '0.00000000',
                investments: '0.00000000',
                otherIncomes: '0.00000000',
                currentIncomesTotal: '0.00000000',
                previousPeriodSavings: '0.00000000',
                totalInflows: '0.00000000',
                totalExpenses: '0.00000000',
                paidExpenses: '0.00000000',
                pendingExpenses: '0.00000000',
                netLiquidity: '0.00000000',
                previousPeriodLabel: null,
            );
        }

        $salary = '0.00000000';
        $sideProjects = '0.00000000';
        $investments = '0.00000000';
        $otherIncomes = '0.00000000';
        $paidExpenses = '0.00000000';
        $pendingExpenses = '0.00000000';

        foreach ($period->items as $item) {
            $itemTotal = bcadd($this->toNumeric($item->amount_q1), $this->toNumeric($item->amount_q2), 8);

            if ($item->item_type === 'income') {
                if ($item->income_category === BudgetItem::INCOME_CATEGORY_SALARY) {
                    $salary = bcadd($salary, $itemTotal, 8);
                } elseif ($item->income_category === BudgetItem::INCOME_CATEGORY_SIDE_PROJECTS) {
                    $sideProjects = bcadd($sideProjects, $itemTotal, 8);
                } elseif ($item->income_category === BudgetItem::INCOME_CATEGORY_INVESTMENTS) {
                    $investments = bcadd($investments, $itemTotal, 8);
                } else {
                    $otherIncomes = bcadd($otherIncomes, $itemTotal, 8);
                }
            } elseif ($item->item_type === 'expense') {
                if ($item->status === 'paid') {
                    $paidExpenses = bcadd($paidExpenses, $itemTotal, 8);
                } else {
                    $pendingExpenses = bcadd($pendingExpenses, $itemTotal, 8);
                }
            }
        }

        $totalExpenses = $paidExpenses;

        $currentIncomesTotal = bcadd(
            bcadd($salary, $sideProjects, 8),
            bcadd($investments, $otherIncomes, 8),
            8
        );

        $previousPeriodSavings = '0.00000000';
        $previousPeriodLabel = null;

        /** @var BudgetPeriod|null $prevPeriod */
        $prevPeriod = BudgetPeriod::query()
            ->where('workspace_id', $workspaceId)
            ->where('period_start', '<', $period->period_start)
            ->with('items')
            ->latest('period_start')
            ->first();

        if ($prevPeriod !== null) {
            $prevIncomes = '0.00000000';
            $prevExpenses = '0.00000000';

            foreach ($prevPeriod->items as $prevItem) {
                $prevTotal = bcadd($this->toNumeric($prevItem->amount_q1), $this->toNumeric($prevItem->amount_q2), 8);
                if ($prevItem->item_type === 'income') {
                    $prevIncomes = bcadd($prevIncomes, $prevTotal, 8);
                } elseif ($prevItem->item_type === 'expense') {
                    $prevExpenses = bcadd($prevExpenses, $prevTotal, 8);
                }
            }

            $diff = bcsub($prevIncomes, $prevExpenses, 8);
            if (bccomp($diff, '0', 8) > 0) {
                $previousPeriodSavings = $diff;
            }

            $previousPeriodLabel = Carbon::parse($prevPeriod->period_start)->translatedFormat('F Y');
        } else {
            /** @var Collection<int, Account> $accounts */
            $accounts = Account::query()
                ->where('workspace_id', $workspaceId)
                ->where('is_active', true)
                ->get();

            $totalAccBalanceUsd = '0.00000000';
            foreach ($accounts as $acc) {
                $totalAccBalanceUsd = bcadd($totalAccBalanceUsd, $this->toNumeric($acc->getBalanceInUsd()), 8);
            }

            $accBalance = $totalAccBalanceUsd;

            if ((float) $accBalance > 0) {
                $periodItemIds = $period->items->pluck('id')->filter()->all();
                if (! empty($periodItemIds)) {
                    $periodTxNet = FinancialTransaction::query()
                        ->where('workspace_id', $workspaceId)
                        ->whereIn('budget_item_id', $periodItemIds)
                        ->selectRaw("SUM(CASE WHEN transaction_type = 'income' THEN amount_usd ELSE -amount_usd END) as net_impact")
                        ->value('net_impact');

                    if ($periodTxNet !== null && is_numeric($periodTxNet)) {
                        $accBalance = (float) $accBalance - (float) $periodTxNet;
                    }
                }

                $previousPeriodSavings = number_format(max(0.0, (float) $accBalance), 8, '.', '');
                $previousPeriodLabel = 'Cuentas / Saldo Inicial';
            }
        }

        $totalInflows = bcadd($currentIncomesTotal, $previousPeriodSavings, 8);
        $netLiquidity = bcsub($totalInflows, $totalExpenses, 8);

        return new LiquidityMetricsDTO(
            salary: $salary,
            sideProjects: $sideProjects,
            investments: $investments,
            otherIncomes: $otherIncomes,
            currentIncomesTotal: $currentIncomesTotal,
            previousPeriodSavings: $previousPeriodSavings,
            totalInflows: $totalInflows,
            totalExpenses: $totalExpenses,
            paidExpenses: $paidExpenses,
            pendingExpenses: $pendingExpenses,
            netLiquidity: $netLiquidity,
            previousPeriodLabel: $previousPeriodLabel,
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
