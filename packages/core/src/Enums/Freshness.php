<?php

declare(strict_types=1);

namespace Beacon\Core\Enums;

enum Freshness: string
{
    case Aggregate = 'aggregate';
    case Realtime = 'realtime';

    public static function default(): self
    {
        return self::Aggregate;
    }

    public function isRealtime(): bool
    {
        return $this === self::Realtime;
    }

    /**
     * Maximum acceptable data delay in minutes.
     * Realtime KPIs read directly from kpi_events (0 delay).
     * Aggregate KPIs are refreshed every N minutes (default 5).
     */
    public function maxDelayMinutes(): int
    {
        return match ($this) {
            self::Realtime => 0,
            self::Aggregate => 5,
        };
    }
}
