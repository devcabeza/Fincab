<?php

declare(strict_types=1);

namespace App\Application\Goal\DTOs;

final readonly class GoalOverviewDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $sinkingFundGoals
     * @param  array<string, mixed>|null  $monthlyRevenueGoal
     * @param  numeric-string  $totalSavedInGoals
     */
    public function __construct(
        public array $sinkingFundGoals,
        public ?array $monthlyRevenueGoal,
        public bool $hasActiveUncoveredDebts,
        public string $totalSavedInGoals,
        public int $activeGoalsCount,
        public int $completedGoalsCount,
    ) {}
}
