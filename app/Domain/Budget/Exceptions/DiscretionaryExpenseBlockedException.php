<?php

declare(strict_types=1);

namespace App\Domain\Budget\Exceptions;

use DomainException;

final class DiscretionaryExpenseBlockedException extends DomainException
{
    public static function dueToPendingQ1Debts(): self
    {
        return new self('Discretionary spending is blocked after day 15 because active Q1 debt payments remain pending.');
    }
}
