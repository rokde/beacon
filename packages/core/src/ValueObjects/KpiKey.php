<?php

declare(strict_types=1);

namespace Beacon\Core\ValueObjects;

use Beacon\Core\Exceptions\InvalidKpiKeyException;
use Stringable;

final readonly class KpiKey implements Stringable
{
    private const int MAX_LENGTH = 64;

    private const string PATTERN = '/^[a-zA-Z0-9_-]+$/';

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $key): self
    {
        if ($key === '') {
            throw InvalidKpiKeyException::empty();
        }

        if (strlen($key) > self::MAX_LENGTH) {
            throw InvalidKpiKeyException::tooLong($key, self::MAX_LENGTH);
        }

        if (preg_match(self::PATTERN, $key) !== 1) {
            throw InvalidKpiKeyException::invalidCharacters($key);
        }

        return new self($key);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
