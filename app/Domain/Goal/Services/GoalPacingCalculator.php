<?php

declare(strict_types=1);

namespace App\Domain\Goal\Services;

use App\Domain\Goal\Enums\PacingStatus;
use App\Domain\Goal\Exceptions\InvalidGoalTargetException;
use DateTimeImmutable;

final class GoalPacingCalculator
{
    /**
     * @param  numeric-string  $targetAmount
     * @param  numeric-string  $currentAmount
     * @param  numeric-string|null  $monthlyContributionRate
     * @return array{
     *     target_amount: numeric-string,
     *     current_amount: numeric-string,
     *     remaining_amount: numeric-string,
     *     completion_percent: numeric-string,
     *     required_monthly_quota: numeric-string,
     *     total_months: int,
     *     months_remaining: int,
     *     status: PacingStatus,
     *     projected_completion_date: DateTimeImmutable
     * }
     */
    public function calculatePacing(
        string $targetAmount,
        string $currentAmount,
        DateTimeImmutable $startDate,
        DateTimeImmutable $targetDate,
        DateTimeImmutable $currentDate,
        ?string $monthlyContributionRate = null
    ): array {
        if (bccomp($targetAmount, '0', 8) <= 0) {
            throw InvalidGoalTargetException::nonPositiveTarget();
        }

        if ($targetDate <= $startDate) {
            throw InvalidGoalTargetException::pastTargetDate();
        }

        $remaining = bcsub($targetAmount, $currentAmount, 8);
        if (bccomp($remaining, '0', 8) <= 0) {
            return [
                'target_amount' => $targetAmount,
                'current_amount' => $currentAmount,
                'remaining_amount' => '0.00000000',
                'completion_percent' => '100.00',
                'required_monthly_quota' => '0.00000000',
                'total_months' => 0,
                'months_remaining' => 0,
                'status' => PacingStatus::Completed,
                'projected_completion_date' => $currentDate,
            ];
        }

        $completionPercent = bcmul(bcdiv($currentAmount, $targetAmount, 8), '100', 2);

        $totalDays = max(1, (int) $startDate->diff($targetDate)->days);
        $totalMonths = max(1, (int) ceil($totalDays / 30.4375));

        $remainingDays = $currentDate < $targetDate ? (int) $currentDate->diff($targetDate)->days : 0;
        $monthsRemaining = max(1, (int) ceil($remainingDays / 30.4375));

        $requiredMonthlyQuota = bcdiv($remaining, (string) $monthsRemaining, 8);

        $elapsedDays = $currentDate > $startDate ? (int) $startDate->diff($currentDate)->days : 0;
        $expectedRatio = sprintf('%.8f', min(1.0, $elapsedDays / $totalDays));
        $expectedAmount = bcmul($targetAmount, $expectedRatio, 8);

        $effectivePace = $monthlyContributionRate !== null && bccomp($monthlyContributionRate, '0', 8) > 0
            ? $monthlyContributionRate
            : $requiredMonthlyQuota;

        $projectedMonths = (float) bcdiv($remaining, $effectivePace, 4);
        $projectedDays = (int) ceil($projectedMonths * 30.4375);
        $projectedCompletionDate = $currentDate->modify("+{$projectedDays} days");

        $status = PacingStatus::OnTrack;
        if ($currentDate >= $targetDate) {
            $status = PacingStatus::Delayed;
        } elseif (bccomp($currentAmount, $expectedAmount, 8) < 0) {
            $status = PacingStatus::Delayed;
        } elseif (bccomp($currentAmount, $expectedAmount, 8) > 0) {
            $status = PacingStatus::Ahead;
        }

        return [
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'remaining_amount' => $remaining,
            'completion_percent' => $completionPercent,
            'required_monthly_quota' => $requiredMonthlyQuota,
            'total_months' => $totalMonths,
            'months_remaining' => $monthsRemaining,
            'status' => $status,
            'projected_completion_date' => $projectedCompletionDate,
        ];
    }

    /**
     * Interactive What-If simulation engine.
     *
     * @param  numeric-string  $targetAmount
     * @param  numeric-string  $currentAmount
     * @param  numeric-string  $baselineMonthlyContribution
     * @param  numeric-string  $simulatedMonthlyContribution
     * @param  numeric-string  $oneOffContribution
     * @return array{
     *     target_amount: numeric-string,
     *     simulated_current_amount: numeric-string,
     *     remaining_amount: numeric-string,
     *     baseline_months: float,
     *     simulated_months: float,
     *     months_difference: float,
     *     is_accelerated: bool,
     *     is_delayed: bool,
     *     baseline_completion_date: DateTimeImmutable,
     *     simulated_completion_date: DateTimeImmutable
     * }
     */
    public function simulateWhatIf(
        string $targetAmount,
        string $currentAmount,
        string $baselineMonthlyContribution,
        string $simulatedMonthlyContribution,
        string $oneOffContribution = '0.00000000',
        ?DateTimeImmutable $asOfDate = null
    ): array {
        $now = $asOfDate ?? new DateTimeImmutable('today');

        $effectiveCurrent = bcadd($currentAmount, $oneOffContribution, 8);
        $remaining = bcsub($targetAmount, $effectiveCurrent, 8);

        if (bccomp($remaining, '0', 8) <= 0) {
            return [
                'target_amount' => $targetAmount,
                'simulated_current_amount' => $effectiveCurrent,
                'remaining_amount' => '0.00000000',
                'baseline_months' => 0.0,
                'simulated_months' => 0.0,
                'months_difference' => 0.0,
                'is_accelerated' => true,
                'is_delayed' => false,
                'baseline_completion_date' => $now,
                'simulated_completion_date' => $now,
            ];
        }

        $safeBaseline = bccomp($baselineMonthlyContribution, '0', 8) > 0 ? $baselineMonthlyContribution : '1.00000000';
        $safeSimulated = bccomp($simulatedMonthlyContribution, '0', 8) > 0 ? $simulatedMonthlyContribution : '1.00000000';

        $baselineRemaining = bcsub($targetAmount, $currentAmount, 8);
        $baselineMonths = (float) bcdiv($baselineRemaining, $safeBaseline, 2);
        $simulatedMonths = (float) bcdiv($remaining, $safeSimulated, 2);

        $monthsDiff = round($simulatedMonths - $baselineMonths, 2);

        $baselineDays = (int) ceil($baselineMonths * 30.4375);
        $simulatedDays = (int) ceil($simulatedMonths * 30.4375);

        $baselineDate = $now->modify("+{$baselineDays} days");
        $simulatedDate = $now->modify("+{$simulatedDays} days");

        return [
            'target_amount' => $targetAmount,
            'simulated_current_amount' => $effectiveCurrent,
            'remaining_amount' => $remaining,
            'baseline_months' => $baselineMonths,
            'simulated_months' => $simulatedMonths,
            'months_difference' => $monthsDiff,
            'is_accelerated' => $monthsDiff < 0,
            'is_delayed' => $monthsDiff > 0,
            'baseline_completion_date' => $baselineDate,
            'simulated_completion_date' => $simulatedDate,
        ];
    }
}
