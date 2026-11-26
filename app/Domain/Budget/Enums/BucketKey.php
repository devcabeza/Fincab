<?php

declare(strict_types=1);

namespace App\Domain\Budget\Enums;

enum BucketKey: string
{
    case Essentials = 'essentials';
    case Debts = 'debts';
    case Priorities = 'priorities';
    case DigitalInvestments = 'digital_investments';
    case Discretionary = 'discretionary';
}
