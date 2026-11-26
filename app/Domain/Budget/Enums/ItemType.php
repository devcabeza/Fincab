<?php

declare(strict_types=1);

namespace App\Domain\Budget\Enums;

enum ItemType: string
{
    case Income = 'income';
    case Expense = 'expense';
}
