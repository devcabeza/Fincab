<?php

declare(strict_types=1);

namespace App\Application\Goal\DTOs;

final readonly class SimulateGoalDTO
{
    /**
     * @param  numeric-string  $simulatedMonthlyContribution
     * @param  numeric-string  $oneOffContribution
     */
    public function __construct(
        public int $goalId,
        public string $simulatedMonthlyContribution,
        public string $oneOffContribution = '0.00000000',
    ) {}
}
