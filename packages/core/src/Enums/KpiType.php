<?php

declare(strict_types=1);

namespace Beacon\Core\Enums;

enum KpiType: string
{
    case SimpleCounter = 'simple_counter';
    case DecrementCounter = 'decrement_counter';
    case Gauge = 'gauge';
    case Rate = 'rate';
    case Ratio = 'ratio';
    case Duration = 'duration';

    public function isCounter(): bool
    {
        return match ($this) {
            self::SimpleCounter, self::DecrementCounter => true,
            default => false,
        };
    }

    public function isPercentage(): bool
    {
        return $this === self::Ratio;
    }

    public function isGauge(): bool
    {
        return $this === self::Gauge;
    }
}
