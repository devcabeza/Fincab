<?php

declare(strict_types=1);

namespace App\Domain\Budget\Enums;

enum DebtStrategy: string
{
    case Snowball = 'snowball';
    case Avalanche = 'avalanche';
}
