<?php

declare(strict_types=1);

namespace App\Domain\Goal\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Paused = 'paused';
    case BlockedByDebt = 'blocked_by_debt';
}
