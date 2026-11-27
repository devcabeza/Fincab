<?php

declare(strict_types=1);

namespace App\Domain\Goal\Enums;

enum PacingStatus: string
{
    case OnTrack = 'on_track';
    case Ahead = 'ahead';
    case Delayed = 'delayed';
    case Completed = 'completed';
    case Underfunded = 'underfunded';
}
