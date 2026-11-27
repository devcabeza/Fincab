<?php

declare(strict_types=1);

namespace App\Application\Goal\Actions;

use App\Application\Goal\DTOs\SimulateGoalDTO;
use App\Domain\Goal\Enums\GoalType;
use App\Domain\Goal\Services\GoalPacingCalculator;
use App\Models\Goal;
use DateTimeImmutable;
use InvalidArgumentException;

final class SimulateGoalScenarioAction
{
    public function __construct(
        private readonly GoalPacingCalculator $pacingCalculator = new GoalPacingCalculator,
    ) {}

    /**
     * @return array{
     *     goal_id: int,
     *     target_amount: numeric-string,
     *     simulated_current_amount: numeric-string,
     *     remaining_amount: numeric-string,
     *     baseline_months: float,
     *     simulated_months: float,
     *     months_difference: float,
     *     is_accelerated: bool,
     *     is_delayed: bool,
     *     baseline_completion_date: string,
     *     simulated_completion_date: string
     * }
     */
    public function execute(SimulateGoalDTO $dto): array
    {
        /** @var Goal $goal */
        $goal = Goal::query()->findOrFail($dto->goalId);

        if ($goal->type !== GoalType::TargetAmount) {
            throw new InvalidArgumentException('El simulador What-If aplica únicamente a metas con monto objetivo acumulativo.');
        }

        $target = (string) $goal->target_amount;
        $current = (string) $goal->current_amount;
        $baselineQuota = (string) $goal->planned_monthly_quota;
        $simulatedMonthly = $this->toNumeric($dto->simulatedMonthlyContribution);
        $oneOff = $this->toNumeric($dto->oneOffContribution);

        $result = $this->pacingCalculator->simulateWhatIf(
            targetAmount: $target,
            currentAmount: $current,
            baselineMonthlyContribution: $baselineQuota,
            simulatedMonthlyContribution: $simulatedMonthly,
            oneOffContribution: $oneOff,
            asOfDate: new DateTimeImmutable('today'),
        );

        return [
            'goal_id' => (int) $goal->id,
            'target_amount' => $result['target_amount'],
            'simulated_current_amount' => $result['simulated_current_amount'],
            'remaining_amount' => $result['remaining_amount'],
            'baseline_months' => $result['baseline_months'],
            'simulated_months' => $result['simulated_months'],
            'months_difference' => $result['months_difference'],
            'is_accelerated' => $result['is_accelerated'],
            'is_delayed' => $result['is_delayed'],
            'baseline_completion_date' => $result['baseline_completion_date']->format('Y-m-d'),
            'simulated_completion_date' => $result['simulated_completion_date']->format('Y-m-d'),
        ];
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
