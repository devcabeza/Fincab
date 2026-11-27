<?php

declare(strict_types=1);

namespace App\Domain\Goal\Services;

use App\Domain\Goal\Exceptions\GoalContributionBlockedByDebtException;

final class DebtGatekeeper
{
    /**
     * Returns true if contributions to discretionary goals are permitted.
     * Blocked when there are active overdue debts or uncovered minimum payments in the period.
     */
    public function canContribute(bool $hasActiveUncoveredDebts): bool
    {
        return ! $hasActiveUncoveredDebts;
    }

    /**
     * Enforces the hard business invariant: asserts that contributions are permitted.
     *
     * @throws GoalContributionBlockedByDebtException
     */
    public function assertCanContribute(bool $hasActiveUncoveredDebts): void
    {
        if ($hasActiveUncoveredDebts) {
            throw GoalContributionBlockedByDebtException::activeDebtsUncovered();
        }
    }
}
