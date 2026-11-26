<?php

declare(strict_types=1);

namespace App\Domain\Budget\Enums;

enum SplitMode: string
{
    case Equal = 'equal';
    case Manual = 'manual';
}
