<?php

declare(strict_types=1);

namespace App\Domain\Budget\Services;

use App\Domain\Budget\Enums\DebtStrategy;

final class DebtPrioritizer
{
    /**
     * @param  list<array{id: int, current_balance: numeric-string, annual_interest_rate: numeric-string}>  $debts
     * @return list<array{id: int, current_balance: numeric-string, annual_interest_rate: numeric-string}>
     */
    public function prioritize(array $debts, DebtStrategy $strategy): array
    {
        $sorted = $debts;

        usort($sorted, function (array $a, array $b) use ($strategy): int {
            if ($strategy === DebtStrategy::Snowball) {
                $balanceComparison = bccomp($a['current_balance'], $b['current_balance'], 8);
                if ($balanceComparison !== 0) {
                    return $balanceComparison;
                }

                $rateComparison = bccomp($b['annual_interest_rate'], $a['annual_interest_rate'], 8);
                if ($rateComparison !== 0) {
                    return $rateComparison;
                }

                return $a['id'] <=> $b['id'];
            }

            $rateComparison = bccomp($b['annual_interest_rate'], $a['annual_interest_rate'], 8);
            if ($rateComparison !== 0) {
                return $rateComparison;
            }

            $balanceComparison = bccomp($a['current_balance'], $b['current_balance'], 8);
            if ($balanceComparison !== 0) {
                return $balanceComparison;
            }

            return $a['id'] <=> $b['id'];
        });

        return $sorted;
    }
}
