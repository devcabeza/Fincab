<?php

declare(strict_types=1);

namespace App\Domain\Budget\Exceptions;

use DomainException;

final class ProtectedDebtMinimumException extends DomainException
{
    public static function cannotReduceBelowMinimum(string $minimumAmount, string $attemptedAmount): self
    {
        return new self("Cannot reduce protected debt budget item below its minimum payment of {$minimumAmount} (attempted: {$attemptedAmount}).");
    }
}
