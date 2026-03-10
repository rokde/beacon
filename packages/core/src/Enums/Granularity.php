<?php

declare(strict_types=1);

namespace Beacon\Core\Enums;

use DateTimeImmutable;

enum Granularity: string
{
    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    /**
     * Minute granularity is opt-in only — it generates high storage volume
     * and should only be used for realtime KPIs or alerting purposes.
     */
    public function isRealtimeOnly(): bool
    {
        return $this === self::Minute;
    }

    /**
     * The default set of granularities used when no explicit granularities
     * are configured on a KpiDefinition. Excludes Minute intentionally.
     *
     * @return list<self>
     */
    public static function defaults(): array
    {
        return [
            self::Hour,
            self::Day,
            self::Week,
            self::Month,
            self::Year,
        ];
    }

    /**
     * Calculate the start of the period that contains the given datetime
     * for this granularity.
     */
    public function periodStart(DateTimeImmutable $datetime): DateTimeImmutable
    {
        return match ($this) {
            self::Minute => $datetime->setTime(
                (int) $datetime->format('H'),
                (int) $datetime->format('i'),
                0,
            ),

            self::Hour => $datetime->setTime(
                (int) $datetime->format('H'),
                0,
                0,
            ),

            self::Day => $datetime->setTime(0, 0, 0),

            self::Week => (function () use ($datetime): DateTimeImmutable {
                // ISO 8601: week starts on Monday (day 1)
                $dayOfWeek = (int) $datetime->format('N'); // 1=Mon, 7=Sun
                $daysToMonday = $dayOfWeek - 1;

                return $datetime
                    ->modify("-{$daysToMonday} days")
                    ->setTime(0, 0, 0);
            })(),

            self::Month => new DateTimeImmutable(
                $datetime->format('Y-m-01 00:00:00'),
                $datetime->getTimezone(),
            ),

            self::Year => new DateTimeImmutable(
                $datetime->format('Y-01-01 00:00:00'),
                $datetime->getTimezone(),
            ),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Minute => 'Per Minute',
            self::Hour   => 'Hourly',
            self::Day    => 'Daily',
            self::Week   => 'Weekly',
            self::Month  => 'Monthly',
            self::Year   => 'Yearly',
        };
    }
}
