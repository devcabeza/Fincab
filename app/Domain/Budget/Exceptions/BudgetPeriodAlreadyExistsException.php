<?php

declare(strict_types=1);

namespace App\Domain\Budget\Exceptions;

use DomainException;

final class BudgetPeriodAlreadyExistsException extends DomainException
{
    public static function forPeriod(string $periodStart): self
    {
        return new self("A budget period starting on {$periodStart} already exists in this workspace.");
    }
}
