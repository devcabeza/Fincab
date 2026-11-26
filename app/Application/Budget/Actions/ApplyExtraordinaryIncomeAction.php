<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\ApplyExtraordinaryIncomeDTO;
use App\Application\Budget\DTOs\ExtraordinaryIncomeResultDTO;
use App\Domain\Budget\Enums\DebtStrategy;
use App\Domain\Budget\Enums\SplitMode;
use App\Domain\Budget\Services\DebtPrioritizer;
use App\Domain\Budget\Services\QuincenaCalculator;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ApplyExtraordinaryIncomeAction
{
    public function __construct(
        private readonly DebtPrioritizer $prioritizer = new DebtPrioritizer,
        private readonly QuincenaCalculator $quincenaCalculator = new QuincenaCalculator,
    ) {}

    public function execute(ApplyExtraordinaryIncomeDTO $dto): ExtraordinaryIncomeResultDTO
    {
        return DB::transaction(function () use ($dto): ExtraordinaryIncomeResultDTO {
            /** @var BudgetPeriod $period */
            $period = BudgetPeriod::query()->with('buckets')->findOrFail($dto->budgetPeriodId);

            $incomeAmount = $this->toNumeric($dto->incomeAmount);
            $accelerationPercent = $this->toNumeric($dto->accelerationPercent);

            $acceleratedAmount = bcdiv(bcmul($incomeAmount, $accelerationPercent, 8), '100', 8);
            $remainingIncome = bcsub($incomeAmount, $acceleratedAmount, 8);

            $incomeSplit = $this->quincenaCalculator->calculateSplit($incomeAmount, SplitMode::Equal);

            /** @var BudgetItem $incomeItem */
            $incomeItem = BudgetItem::query()->create([
                'workspace_id' => $dto->workspaceId,
                'budget_period_id' => $period->id,
                'title' => trim($dto->incomeTitle),
                'item_type' => 'income',
                'split_mode' => 'equal',
                'amount_q1' => $incomeSplit['q1'],
                'amount_q2' => $incomeSplit['q2'],
                'status' => 'paid',
                'is_recurring' => false,
                'minimum_payment_protected' => false,
            ]);

            /** @var Collection<int, Debt> $debts */
            $debts = Debt::query()
                ->where('workspace_id', $dto->workspaceId)
                ->where('is_active', true)
                ->where('current_balance', '>', 0)
                ->get();

            $debtsArray = [];
            foreach ($debts as $debt) {
                $debtsArray[] = [
                    'id' => (int) $debt->id,
                    'current_balance' => $this->toNumeric($debt->current_balance),
                    'annual_interest_rate' => $this->toNumeric($debt->annual_interest_rate),
                ];
            }

            if (count($debtsArray) === 0 || bccomp($acceleratedAmount, '0', 8) <= 0) {
                return new ExtraordinaryIncomeResultDTO(
                    incomeBudgetItemId: $incomeItem->id,
                    extraordinaryBudgetItemId: null,
                    allocatedDebtId: null,
                    acceleratedAmount: '0.00000000',
                    remainingIncomeAmount: $incomeAmount,
                );
            }

            $strategy = DebtStrategy::tryFrom((string) $period->debt_strategy) ?? DebtStrategy::Avalanche;
            $prioritized = $this->prioritizer->prioritize($debtsArray, $strategy);
            $topDebtRecord = $prioritized[0];
            $allocatedDebtId = $topDebtRecord['id'];

            /** @var Debt $topDebt */
            $topDebt = $debts->firstWhere('id', $allocatedDebtId);

            $debtsBucket = $period->buckets->firstWhere('key', 'debts');
            if ($debtsBucket === null) {
                $debtsBucket = BudgetBucket::query()->create([
                    'workspace_id' => $dto->workspaceId,
                    'budget_period_id' => $period->id,
                    'key' => 'debts',
                    'name' => 'Deudas',
                    'priority' => 2,
                ]);
            }

            $accelSplit = $this->quincenaCalculator->calculateSplit($acceleratedAmount, SplitMode::Equal);

            /** @var BudgetItem $extraordinaryItem */
            $extraordinaryItem = BudgetItem::query()->create([
                'workspace_id' => $dto->workspaceId,
                'budget_period_id' => $period->id,
                'budget_bucket_id' => $debtsBucket->id,
                'debt_id' => $topDebt->id,
                'title' => 'Abono Extraordinario: '.$topDebt->name,
                'item_type' => 'expense',
                'split_mode' => 'equal',
                'amount_q1' => $accelSplit['q1'],
                'amount_q2' => $accelSplit['q2'],
                'status' => 'pending',
                'is_recurring' => false,
                'minimum_payment_protected' => false,
            ]);

            return new ExtraordinaryIncomeResultDTO(
                incomeBudgetItemId: $incomeItem->id,
                extraordinaryBudgetItemId: $extraordinaryItem->id,
                allocatedDebtId: $topDebt->id,
                acceleratedAmount: $acceleratedAmount,
                remainingIncomeAmount: $remainingIncome,
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
