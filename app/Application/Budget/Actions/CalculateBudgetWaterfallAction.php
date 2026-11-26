<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\BudgetWaterfallResultDTO;
use App\Application\Budget\DTOs\CalculateBudgetWaterfallDTO;
use App\Domain\Budget\Enums\DebtStrategy;
use App\Domain\Budget\Enums\SplitMode;
use App\Domain\Budget\Services\BudgetWaterfallCalculator;
use App\Domain\Budget\Services\QuincenaCalculator;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CalculateBudgetWaterfallAction
{
    public function __construct(
        private readonly BudgetWaterfallCalculator $waterfallCalculator = new BudgetWaterfallCalculator,
        private readonly QuincenaCalculator $quincenaCalculator = new QuincenaCalculator,
    ) {}

    public function execute(CalculateBudgetWaterfallDTO $dto): BudgetWaterfallResultDTO
    {
        return DB::transaction(function () use ($dto): BudgetWaterfallResultDTO {
            $period = BudgetPeriod::query()->with(['workspace', 'buckets', 'items'])->findOrFail($dto->budgetPeriodId);

            $totalIncome = '0.00000000';
            if ($dto->confirmedIncome !== null) {
                $totalIncome = $this->toNumeric($dto->confirmedIncome);
            } else {
                $incomeItems = $period->items->where('item_type', 'income');
                foreach ($incomeItems as $incomeItem) {
                    $itemTotal = bcadd($this->toNumeric($incomeItem->amount_q1), $this->toNumeric($incomeItem->amount_q2), 8);
                    $totalIncome = bcadd($totalIncome, $itemTotal, 8);
                }
            }

            $essentialsExpenses = '0.00000000';
            $essentialsBucket = $period->buckets->firstWhere('key', 'essentials');
            if ($essentialsBucket !== null) {
                $essentialItems = $period->items->where('budget_bucket_id', $essentialsBucket->id)->where('item_type', 'expense');
                foreach ($essentialItems as $item) {
                    $itemTotal = bcadd($this->toNumeric($item->amount_q1), $this->toNumeric($item->amount_q2), 8);
                    $essentialsExpenses = bcadd($essentialsExpenses, $itemTotal, 8);
                }
            }

            /** @var Collection<int, Debt> $debts */
            $debts = Debt::query()
                ->where('workspace_id', $period->workspace_id)
                ->where('is_active', true)
                ->get();

            $debtsArray = [];
            foreach ($debts as $debt) {
                $debtsArray[] = [
                    'id' => (int) $debt->id,
                    'current_balance' => $this->toNumeric($debt->current_balance),
                    'annual_interest_rate' => $this->toNumeric($debt->annual_interest_rate),
                    'minimum_payment' => $this->toNumeric($debt->minimum_payment),
                ];
            }

            $bucketPercentages = [];
            foreach ($period->buckets as $bucket) {
                if ($bucket->key !== 'essentials' && $bucket->key !== 'debts') {
                    $bucketPercentages[$bucket->key] = $this->toNumeric($bucket->allocation_percent);
                }
            }

            $strategy = DebtStrategy::tryFrom((string) $period->debt_strategy) ?? DebtStrategy::Avalanche;
            $extraPercent = $this->toNumeric($period->extra_debt_payment_percent);

            $result = $this->waterfallCalculator->calculate(
                totalIncome: $totalIncome,
                essentialsExpenses: $essentialsExpenses,
                debts: $debtsArray,
                extraDebtPaymentPercent: $extraPercent,
                strategy: $strategy,
                bucketPercentages: $bucketPercentages,
            );

            $debtsBucket = $period->buckets->firstWhere('key', 'debts');
            if ($debtsBucket === null) {
                $debtsBucket = BudgetBucket::query()->create([
                    'workspace_id' => $period->workspace_id,
                    'budget_period_id' => $period->id,
                    'key' => 'debts',
                    'name' => 'Deudas',
                    'allocation_percent' => '0.00',
                    'priority' => 2,
                ]);
            }

            foreach ($debts as $debt) {
                $minPayment = $this->toNumeric($debt->minimum_payment);
                if (bccomp($minPayment, '0', 8) <= 0) {
                    BudgetItem::query()
                        ->where('budget_period_id', $period->id)
                        ->where('debt_id', $debt->id)
                        ->where('minimum_payment_protected', true)
                        ->delete();

                    continue;
                }

                $split = $this->quincenaCalculator->calculateSplit(
                    total: $minPayment,
                    mode: SplitMode::Equal,
                );

                $protectedItem = BudgetItem::query()
                    ->where('budget_period_id', $period->id)
                    ->where('debt_id', $debt->id)
                    ->where('minimum_payment_protected', true)
                    ->first();

                if ($protectedItem !== null) {
                    $protectedItem->update([
                        'title' => $debt->name,
                        'amount_q1' => $split['q1'],
                        'amount_q2' => $split['q2'],
                        'split_mode' => 'equal',
                    ]);
                } else {
                    BudgetItem::query()->create([
                        'workspace_id' => $period->workspace_id,
                        'budget_period_id' => $period->id,
                        'budget_bucket_id' => $debtsBucket->id,
                        'debt_id' => $debt->id,
                        'title' => $debt->name,
                        'item_type' => 'expense',
                        'split_mode' => 'equal',
                        'amount_q1' => $split['q1'],
                        'amount_q2' => $split['q2'],
                        'status' => 'pending',
                        'is_recurring' => true,
                        'minimum_payment_protected' => true,
                    ]);
                }
            }

            if ($result['acceleratorDebtId'] !== null && bccomp($result['acceleratorAmount'], '0', 8) > 0) {
                $targetDebt = $debts->firstWhere('id', $result['acceleratorDebtId']);
                $accelSplit = $this->quincenaCalculator->calculateSplit(
                    total: $result['acceleratorAmount'],
                    mode: SplitMode::Equal,
                );

                $accelItem = BudgetItem::query()
                    ->where('budget_period_id', $period->id)
                    ->where('debt_id', $result['acceleratorDebtId'])
                    ->where('minimum_payment_protected', false)
                    ->first();

                $targetName = $targetDebt instanceof Debt ? $targetDebt->name : 'Deuda #'.$result['acceleratorDebtId'];
                $title = 'Abono Extraordinario: '.$targetName;

                if ($accelItem !== null) {
                    $accelItem->update([
                        'title' => $title,
                        'amount_q1' => $accelSplit['q1'],
                        'amount_q2' => $accelSplit['q2'],
                        'split_mode' => 'equal',
                    ]);
                } else {
                    BudgetItem::query()->create([
                        'workspace_id' => $period->workspace_id,
                        'budget_period_id' => $period->id,
                        'budget_bucket_id' => $debtsBucket->id,
                        'debt_id' => $result['acceleratorDebtId'],
                        'title' => $title,
                        'item_type' => 'expense',
                        'split_mode' => 'equal',
                        'amount_q1' => $accelSplit['q1'],
                        'amount_q2' => $accelSplit['q2'],
                        'status' => 'pending',
                        'is_recurring' => false,
                        'minimum_payment_protected' => false,
                    ]);
                }
            }

            return new BudgetWaterfallResultDTO(
                totalIncome: $result['totalIncome'],
                essentialsAmount: $result['essentialsAmount'],
                minimumDebtsAmount: $result['minimumDebtsAmount'],
                surplusAfterEssentialsAndMinimums: $result['surplusAfterEssentialsAndMinimums'],
                acceleratorAmount: $result['acceleratorAmount'],
                acceleratorDebtId: $result['acceleratorDebtId'],
                remainingSurplus: $result['remainingSurplus'],
                bucketLimits: $result['bucketLimits'],
            );
        });
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
