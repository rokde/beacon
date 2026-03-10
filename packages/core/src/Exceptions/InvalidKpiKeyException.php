<?php

declare(strict_types=1);

namespace Beacon\Core\Exceptions;

use InvalidArgumentException;

final class InvalidKpiKeyException extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('KPI key must not be empty.');
    }

    public static function tooLong(string $key, int $maxLength): self
    {
        return new self(sprintf(
            'KPI key "%s" exceeds the maximum length of %d characters.',
            $key,
            $maxLength,
        ));
    }

    public static function invalidCharacters(string $key): self
    {
        return new self(sprintf(
            'KPI key "%s" contains invalid characters. Only letters, numbers, underscores and hyphens are allowed.',
            $key,
        ));
    }
}
