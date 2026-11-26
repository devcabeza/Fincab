<?php

declare(strict_types=1);

namespace App\Domain\Budget\Enums;

enum ItemStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
