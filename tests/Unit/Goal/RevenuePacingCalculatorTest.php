<?php

declare(strict_types=1);

use App\Domain\Goal\Enums\PacingStatus;
use App\Domain\Goal\Services\RevenuePacingCalculator;

test('calculates monthly revenue run-rate and required daily rate correctly', function () {
    $calculator = new RevenuePacingCalculator;

    $date = new DateTimeImmutable('2026-11-15'); // day 15 of 30 days in Nov

    $result = $calculator->calculateRevenuePacing(
        monthlyTarget: '2000.00000000',
        currentMonthRevenue: '1000.00000000', // exactly 50% at day 15
        currentDate: $date,
    );

    expect($result['status'])->toBe(PacingStatus::OnTrack)
        ->and($result['completion_percent'])->toBe('50.00')
        ->and($result['remaining_revenue'])->toBe('1000.00000000')
        ->and($result['days_remaining'])->toBe(15)
        ->and((float) $result['required_daily_rate'])->toBeGreaterThan(66.0)
        ->and((float) $result['required_daily_rate'])->toBeLessThan(67.0);
});

test('flags monthly revenue as delayed when run-rate is behind schedule', function () {
    $calculator = new RevenuePacingCalculator;

    $date = new DateTimeImmutable('2026-11-20'); // day 20 of 30 (expected ~66.6% = $1333)

    $result = $calculator->calculateRevenuePacing(
        monthlyTarget: '2000.00000000',
        currentMonthRevenue: '500.00000000', // only 500 at day 20
        currentDate: $date,
    );

    expect($result['status'])->toBe(PacingStatus::Delayed)
        ->and($result['completion_percent'])->toBe('25.00')
        ->and($result['days_remaining'])->toBe(10)
        ->and((float) $result['required_daily_rate'])->toBe(150.0);
});

test('flags monthly revenue as completed when target is met or exceeded', function () {
    $calculator = new RevenuePacingCalculator;

    $date = new DateTimeImmutable('2026-11-25');

    $result = $calculator->calculateRevenuePacing(
        monthlyTarget: '2000.00000000',
        currentMonthRevenue: '2400.00000000',
        currentDate: $date,
    );

    expect($result['status'])->toBe(PacingStatus::Completed)
        ->and($result['remaining_revenue'])->toBe('0.00000000')
        ->and($result['required_daily_rate'])->toBe('0.00000000')
        ->and((float) $result['completion_percent'])->toBe(120.0);
});
