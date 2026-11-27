<?php

declare(strict_types=1);

namespace App\Application\Goal\DTOs;

final readonly class ContributeToGoalDTO
{
    public function __construct(
        public int $workspaceId,
        public int $goalId,
        public string $amount,
        public ?string $notes = null,
        public ?string $occurredAt = null,
    ) {}
}
