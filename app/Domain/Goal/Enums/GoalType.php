<?php

declare(strict_types=1);

namespace App\Domain\Goal\Enums;

enum GoalType: string
{
    case TargetAmount = 'target_amount';
    case MonthlyRevenue = 'monthly_revenue';
}
