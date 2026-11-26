<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

use DateTimeImmutable;

final readonly class GetCashFlowSummaryDTO
{
    public function __construct(
        public int $budgetPeriodId,
        public ?DateTimeImmutable $referenceDate = null,
    ) {}
}
