<?php

declare(strict_types=1);

namespace App\Domain\Budget\Services;

use App\Domain\Budget\Enums\SplitMode;

final class QuincenaCalculator
{
    /**
     * @param  numeric-string  $total
     * @param  numeric-string|null  $manualQ1
     * @param  numeric-string|null  $manualQ2
     * @return array{q1: numeric-string, q2: numeric-string}
     */
    public function calculateSplit(string $total, SplitMode $mode, ?string $manualQ1 = null, ?string $manualQ2 = null): array
    {
        if ($mode === SplitMode::Equal) {
            $q1 = bcdiv($total, '2', 8);
            $q2 = bcsub($total, $q1, 8);

            return [
                'q1' => $q1,
                'q2' => $q2,
            ];
        }

        $q1 = $manualQ1 !== null ? bcadd($manualQ1, '0', 8) : '0.00000000';
        $q2 = $manualQ2 !== null ? bcadd($manualQ2, '0', 8) : '0.00000000';

        return [
            'q1' => $q1,
            'q2' => $q2,
        ];
    }

    /**
     * @param  numeric-string  $q1
     * @param  numeric-string  $q2
     * @return numeric-string
     */
    public function sum(string $q1, string $q2): string
    {
        return bcadd($q1, $q2, 8);
    }

    /**
     * @param  numeric-string  $q1
     * @param  numeric-string  $q2
     * @param  numeric-string  $minimumPayment
     */
    public function meetsMinimum(string $q1, string $q2, string $minimumPayment): bool
    {
        $total = $this->sum($q1, $q2);

        return bccomp($total, $minimumPayment, 8) >= 0;
    }
}
