<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class CloneBudgetPeriodDTO
{
    public function __construct(
        public int $sourcePeriodId,
        public string $targetPeriodStart,
        public ?string $targetBaseCurrency = null,
    ) {}
}
