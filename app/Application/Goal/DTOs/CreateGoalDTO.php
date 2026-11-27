<?php

declare(strict_types=1);

namespace App\Application\Goal\DTOs;

use App\Domain\Goal\Enums\GoalType;

final readonly class CreateGoalDTO
{
    public function __construct(
        public int $workspaceId,
        public string $name,
        public GoalType $type,
        public ?string $targetAmount = null,
        public ?string $monthlyTarget = null,
        public ?string $plannedMonthlyQuota = null,
        public string $startDate = 'today',
        public ?string $targetDate = null,
        public string $currencyCode = 'USD',
        public ?string $notes = null,
    ) {}
}
