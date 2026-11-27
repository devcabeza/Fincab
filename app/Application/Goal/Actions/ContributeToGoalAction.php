<?php

declare(strict_types=1);

namespace App\Application\Goal\Actions;

use App\Application\Goal\DTOs\ContributeToGoalDTO;
use App\Domain\Goal\Enums\GoalStatus;
use App\Domain\Goal\Enums\GoalType;
use App\Domain\Goal\Services\DebtGatekeeper;
use App\Domain\Goal\Services\GoalPacingCalculator;
use App\Models\BudgetItem;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\GoalContribution;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ContributeToGoalAction
{
    public function __construct(
        private readonly DebtGatekeeper $debtGatekeeper = new DebtGatekeeper,
        private readonly GoalPacingCalculator $pacingCalculator = new GoalPacingCalculator,
    ) {}

    public function execute(ContributeToGoalDTO $dto): GoalContribution
    {
        $amount = $this->toNumeric($dto->amount);
        if (bccomp($amount, '0', 8) <= 0) {
            throw new InvalidArgumentException('El monto del aporte debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($dto, $amount): GoalContribution {
            /** @var Goal $goal */
            $goal = Goal::query()
                ->where('workspace_id', $dto->workspaceId)
                ->lockForUpdate()
                ->findOrFail($dto->goalId);

            if ($goal->type === GoalType::MonthlyRevenue) {
                throw new InvalidArgumentException('Las metas de facturación se registran a través de transacciones de ingreso, no de aportes directos.');
            }

            // Hard invariant check: block contribution if active debts exist or pending debt items remain
            $hasActiveDebts = Debt::query()
                ->where('workspace_id', $dto->workspaceId)
                ->where('is_active', true)
                ->where('current_balance', '>', 0)
                ->exists();

            $hasPendingDebtBudgetItems = BudgetItem::query()
                ->where('workspace_id', $dto->workspaceId)
                ->whereNotNull('debt_id')
                ->where('status', 'pending')
                ->exists();

            $hasUncoveredDebts = $hasActiveDebts || $hasPendingDebtBudgetItems;
            $this->debtGatekeeper->assertCanContribute($hasUncoveredDebts);

            $occurredAt = $dto->occurredAt ? CarbonImmutable::parse($dto->occurredAt) : CarbonImmutable::now();

            /** @var GoalContribution $contribution */
            $contribution = GoalContribution::query()->create([
                'workspace_id' => $dto->workspaceId,
                'goal_id' => $goal->id,
                'amount' => $amount,
                'currency_code' => $goal->currency_code,
                'occurred_at' => $occurredAt,
                'notes' => $dto->notes ? trim($dto->notes) : null,
            ]);

            $newCurrentAmount = bcadd((string) $goal->current_amount, $amount, 8);
            $targetAmount = (string) $goal->target_amount;

            $status = $goal->status;
            if (bccomp($newCurrentAmount, $targetAmount, 8) >= 0) {
                $status = GoalStatus::Completed;
                $projectedDate = $occurredAt->toDateString();
            } else {
                $targetDateString = $goal->target_date ? $goal->target_date->toDateString() : $occurredAt->addMonths(12)->toDateString();
                $startDate = new DateTimeImmutable($goal->start_date->toDateString());
                $targetDate = new DateTimeImmutable($targetDateString);
                $now = new DateTimeImmutable($occurredAt->toDateString());

                $pacing = $this->pacingCalculator->calculatePacing(
                    targetAmount: $targetAmount,
                    currentAmount: $newCurrentAmount,
                    startDate: $startDate,
                    targetDate: $targetDate,
                    currentDate: $now,
                    monthlyContributionRate: (string) $goal->planned_monthly_quota,
                );

                $projectedDate = $pacing['projected_completion_date']->format('Y-m-d');
            }

            $goal->update([
                'current_amount' => $newCurrentAmount,
                'status' => $status,
                'projected_date' => $projectedDate,
            ]);

            return $contribution;
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
