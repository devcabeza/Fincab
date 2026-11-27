<?php

declare(strict_types=1);

use App\Domain\Goal\Enums\PacingStatus;
use App\Domain\Goal\Exceptions\InvalidGoalTargetException;
use App\Domain\Goal\Services\GoalPacingCalculator;

test('calculates correct monthly quota and pacing status for target goal', function () {
    $calculator = new GoalPacingCalculator;

    $startDate = new DateTimeImmutable('2026-01-01');
    $targetDate = new DateTimeImmutable('2026-11-01'); // 10 months
    $currentDate = new DateTimeImmutable('2026-01-01');

    $result = $calculator->calculatePacing(
        targetAmount: '3000.00000000',
        currentAmount: '0.00000000',
        startDate: $startDate,
        targetDate: $targetDate,
        currentDate: $currentDate,
    );

    expect($result['status'])->toBe(PacingStatus::OnTrack)
        ->and($result['remaining_amount'])->toBe('3000.00000000')
        ->and((float) $result['required_monthly_quota'])->toBeGreaterThanOrEqual(290.0)
        ->and((float) $result['required_monthly_quota'])->toBeLessThanOrEqual(310.0);
});

test('detects delayed pacing when current amount lags behind expected timeline', function () {
    $calculator = new GoalPacingCalculator;

    $startDate = new DateTimeImmutable('2026-01-01');
    $targetDate = new DateTimeImmutable('2026-11-01'); // 10 months total
    $currentDate = new DateTimeImmutable('2026-06-01'); // 5 months elapsed (expected ~1500)

    $result = $calculator->calculatePacing(
        targetAmount: '3000.00000000',
        currentAmount: '500.00000000', // only 500 saved -> delayed!
        startDate: $startDate,
        targetDate: $targetDate,
        currentDate: $currentDate,
    );

    expect($result['status'])->toBe(PacingStatus::Delayed)
        ->and($result['remaining_amount'])->toBe('2500.00000000');
});

test('what-if simulator projects delay when monthly contribution is lowered', function () {
    $calculator = new GoalPacingCalculator;

    $target = '3000.00000000';
    $current = '0.00000000';
    $baselineMonthly = '300.00000000'; // 10 months
    $simulatedMonthly = '100.00000000'; // 30 months

    $simulation = $calculator->simulateWhatIf(
        targetAmount: $target,
        currentAmount: $current,
        baselineMonthlyContribution: $baselineMonthly,
        simulatedMonthlyContribution: $simulatedMonthly,
        oneOffContribution: '0.00000000',
        asOfDate: new DateTimeImmutable('2026-01-01'),
    );

    expect($simulation['is_delayed'])->toBeTrue()
        ->and($simulation['is_accelerated'])->toBeFalse()
        ->and($simulation['months_difference'])->toBe(20.0)
        ->and($simulation['baseline_months'])->toBe(10.0)
        ->and($simulation['simulated_months'])->toBe(30.0);
});

test('what-if simulator projects acceleration when one-off bonus is added', function () {
    $calculator = new GoalPacingCalculator;

    $target = '3000.00000000';
    $current = '500.00000000';
    $baselineMonthly = '300.00000000';
    $simulatedMonthly = '300.00000000';
    $oneOffBonus = '1000.00000000';

    $simulation = $calculator->simulateWhatIf(
        targetAmount: $target,
        currentAmount: $current,
        baselineMonthlyContribution: $baselineMonthly,
        simulatedMonthlyContribution: $simulatedMonthly,
        oneOffContribution: $oneOffBonus,
        asOfDate: new DateTimeImmutable('2026-01-01'),
    );

    expect($simulation['is_accelerated'])->toBeTrue()
        ->and($simulation['months_difference'])->toBeLessThan(0)
        ->and($simulation['simulated_current_amount'])->toBe('1500.00000000');
});

test('throws exception on non-positive target amount or past date', function () {
    $calculator = new GoalPacingCalculator;

    $startDate = new DateTimeImmutable('2026-01-01');
    $pastTargetDate = new DateTimeImmutable('2025-01-01');

    expect(fn () => $calculator->calculatePacing(
        targetAmount: '0.00000000',
        currentAmount: '0.00000000',
        startDate: $startDate,
        targetDate: new DateTimeImmutable('2026-12-01'),
        currentDate: $startDate,
    ))->toThrow(InvalidGoalTargetException::class);

    expect(fn () => $calculator->calculatePacing(
        targetAmount: '1000.00000000',
        currentAmount: '0.00000000',
        startDate: $startDate,
        targetDate: $pastTargetDate,
        currentDate: $startDate,
    ))->toThrow(InvalidGoalTargetException::class);
});
