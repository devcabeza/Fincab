<?php

declare(strict_types=1);

namespace App\Domain\Goal\Exceptions;

use InvalidArgumentException;

final class InvalidGoalTargetException extends InvalidArgumentException
{
    public static function nonPositiveTarget(): self
    {
        return new self('El monto objetivo de la meta debe ser mayor a cero.');
    }

    public static function pastTargetDate(): self
    {
        return new self('La fecha objetivo de la meta debe ser posterior a la fecha de inicio.');
    }
}
