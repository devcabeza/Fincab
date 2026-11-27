<?php

declare(strict_types=1);

namespace App\Domain\Goal\Services;

use App\Domain\Goal\Enums\PacingStatus;
use App\Domain\Goal\Exceptions\InvalidGoalTargetException;
use DateTimeImmutable;

final class RevenuePacingCalculator
{
    /**
     * @param  numeric-string  $monthlyTarget
     * @param  numeric-string  $currentMonthRevenue
     * @return array{
     *     monthly_target: numeric-string,
     *     current_revenue: numeric-string,
     *     remaining_revenue: numeric-string,
     *     completion_percent: numeric-string,
     *     required_daily_rate: numeric-string,
     *     day_of_month: int,
     *     days_in_month: int,
     *     days_remaining: int,
     *     status: PacingStatus
     * }
     */
    public function calculateRevenuePacing(
        string $monthlyTarget,
        string $currentMonthRevenue,
        DateTimeImmutable $currentDate
    ): array {
        if (bccomp($monthlyTarget, '0', 8) <= 0) {
            throw InvalidGoalTargetException::nonPositiveTarget();
        }

        $remaining = bcsub($monthlyTarget, $currentMonthRevenue, 8);
        $isFinished = bccomp($remaining, '0', 8) <= 0;

        $completionPercent = bcmul(bcdiv($currentMonthRevenue, $monthlyTarget, 8), '100', 2);

        $currentDay = (int) $currentDate->format('j');
        $daysInMonth = (int) $currentDate->format('t');
        $daysRemaining = max(0, $daysInMonth - $currentDay);

        $requiredDailyRate = '0.00000000';
        if (! $isFinished && $daysRemaining > 0) {
            $requiredDailyRate = bcdiv($remaining, (string) $daysRemaining, 8);
        }

        if ($isFinished) {
            return [
                'monthly_target' => $monthlyTarget,
                'current_revenue' => $currentMonthRevenue,
                'remaining_revenue' => '0.00000000',
                'completion_percent' => $completionPercent,
                'required_daily_rate' => '0.00000000',
                'day_of_month' => $currentDay,
                'days_in_month' => $daysInMonth,
                'days_remaining' => $daysRemaining,
                'status' => PacingStatus::Completed,
            ];
        }

        $expectedRatio = sprintf('%.8f', (float) ($currentDay / $daysInMonth));
        $expectedRevenueSoFar = bcmul($monthlyTarget, $expectedRatio, 8);

        $status = PacingStatus::OnTrack;
        if (bccomp($currentMonthRevenue, $expectedRevenueSoFar, 8) < 0) {
            $status = PacingStatus::Delayed;
        } elseif (bccomp($currentMonthRevenue, $expectedRevenueSoFar, 8) > 0) {
            $status = PacingStatus::Ahead;
        }

        return [
            'monthly_target' => $monthlyTarget,
            'current_revenue' => $currentMonthRevenue,
            'remaining_revenue' => $remaining,
            'completion_percent' => $completionPercent,
            'required_daily_rate' => $requiredDailyRate,
            'day_of_month' => $currentDay,
            'days_in_month' => $daysInMonth,
            'days_remaining' => $daysRemaining,
            'status' => $status,
        ];
    }
}
