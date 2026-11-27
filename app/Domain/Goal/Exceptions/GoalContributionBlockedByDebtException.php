<?php

declare(strict_types=1);

namespace App\Domain\Goal\Exceptions;

use DomainException;

final class GoalContributionBlockedByDebtException extends DomainException
{
    public static function activeDebtsUncovered(): self
    {
        return new self('Los aportes a metas de ahorro están bloqueados porque existen compromisos de deuda activos sin cubrir en el período.');
    }
}
