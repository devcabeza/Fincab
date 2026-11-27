<?php

declare(strict_types=1);

namespace App\Application\Goal\Actions;

use App\Application\Goal\DTOs\CreateGoalDTO;
use App\Domain\Goal\Enums\GoalStatus;
use App\Domain\Goal\Enums\GoalType;
use App\Domain\Goal\Exceptions\InvalidGoalTargetException;
use App\Domain\Goal\Services\GoalPacingCalculator;
use App\Models\Goal;
use DateTimeImmutable;

final class CreateGoalAction
{
    public function __construct(
        private readonly GoalPacingCalculator $pacingCalculator = new GoalPacingCalculator,
    ) {}

    public function execute(CreateGoalDTO $dto): Goal
    {
        $startDate = new DateTimeImmutable($dto->startDate);

        $targetAmount = '0.00000000';
        $monthlyTarget = '0.00000000';
        $plannedQuota = '0.00000000';
        $projectedDate = null;
        $targetDateObj = null;

        if ($dto->type === GoalType::TargetAmount) {
            $targetAmount = $this->toNumeric($dto->targetAmount);
            if (bccomp($targetAmount, '0', 8) <= 0) {
                throw InvalidGoalTargetException::nonPositiveTarget();
            }

            if ($dto->targetDate === null) {
                throw new InvalidGoalTargetException('Las metas de ahorro requieren una fecha objetivo.');
            }

            $targetDateObj = new DateTimeImmutable($dto->targetDate);
            if ($targetDateObj <= $startDate) {
                throw InvalidGoalTargetException::pastTargetDate();
            }

            $pacing = $this->pacingCalculator->calculatePacing(
                targetAmount: $targetAmount,
                currentAmount: '0.00000000',
                startDate: $startDate,
                targetDate: $targetDateObj,
                currentDate: $startDate,
            );

            $plannedQuota = $dto->plannedMonthlyQuota !== null && bccomp($this->toNumeric($dto->plannedMonthlyQuota), '0', 8) > 0
                ? $this->toNumeric($dto->plannedMonthlyQuota)
                : $pacing['required_monthly_quota'];

            $projectedDate = $pacing['projected_completion_date']->format('Y-m-d');
        } elseif ($dto->type === GoalType::MonthlyRevenue) {
            $monthlyTarget = $this->toNumeric($dto->monthlyTarget);
            if (bccomp($monthlyTarget, '0', 8) <= 0) {
                throw InvalidGoalTargetException::nonPositiveTarget();
            }
        }

        /** @var Goal $goal */
        $goal = Goal::query()->create([
            'workspace_id' => $dto->workspaceId,
            'name' => trim($dto->name),
            'type' => $dto->type,
            'status' => GoalStatus::Active,
            'currency_code' => strtoupper($dto->currencyCode),
            'target_amount' => $targetAmount,
            'current_amount' => '0.00000000',
            'monthly_target' => $monthlyTarget,
            'planned_monthly_quota' => $plannedQuota,
            'start_date' => $startDate->format('Y-m-d'),
            'target_date' => $targetDateObj?->format('Y-m-d'),
            'projected_date' => $projectedDate,
            'notes' => $dto->notes ? trim($dto->notes) : null,
            'is_active' => true,
        ]);

        return $goal;
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
