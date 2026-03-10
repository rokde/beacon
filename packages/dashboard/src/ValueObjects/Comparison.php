<?php

declare(strict_types=1);

namespace Beacon\Dashboard\ValueObjects;

use DateTimeImmutable;

/**
 * Describes a period-over-period comparison for a KPI tile.
 *
 * Examples:
 *   Comparison::previousPeriod()         → same window, one period back
 *   Comparison::offset('-1 week')        → shift start/end by 1 week
 *   Comparison::offset('-3 days', '3 days') → 3-day window, 3 days prior
 */
final class Comparison
{
    private function __construct(
        public readonly string $label,
        public readonly string $offset,
        public readonly ?string $window,
    ) {}

    public static function previousPeriod(): self
    {
        return new self(
            label: 'vs. previous period',
            offset: '-1 period',
            window: null,
        );
    }

    public static function offset(string $offset, ?string $window = null): self
    {
        return new self(
            label: "vs. {$offset}",
            offset: $offset,
            window: $window,
        );
    }

    /**
     * Resolve the comparison start/end datetimes given a reference window.
     *
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
     */
    public function resolve(DateTimeImmutable $currentFrom, DateTimeImmutable $currentTo): array
    {
        if ($this->offset === '-1 period') {
            $windowSeconds = $currentTo->getTimestamp() - $currentFrom->getTimestamp();

            return [
                'from' => $currentFrom->modify("-{$windowSeconds} seconds"),
                'to' => $currentTo->modify("-{$windowSeconds} seconds"),
            ];
        }

        $compFrom = $currentFrom->modify($this->offset);
        $compTo = $this->window !== null
            ? $compFrom->modify("+{$this->window}")
            : $currentTo->modify($this->offset);

        return ['from' => $compFrom, 'to' => $compTo];
    }
}
