<?php

declare(strict_types=1);

namespace Beacon\Core\Contracts;

use Beacon\Core\Enums\Granularity;
use Beacon\Core\ValueObjects\KpiKey;
use DateTimeImmutable;

interface KpiAggregateRepositoryContract
{
    /**
     * Upsert an aggregate value for a given KPI, granularity and period.
     * Idempotent — safe to call multiple times for the same period.
     *
     * @param array<string, mixed> $meta
     */
    public function upsert(
        KpiKey $key,
        Granularity $granularity,
        DateTimeImmutable $periodStart,
        float $value,
        int $eventCount,
        array $meta = [],
    ): void;

    /**
     * Query aggregated values for a KPI within a time range.
     *
     * @return list<object{period_start: string, value: float, count: int, meta: string}>
     */
    public function query(
        KpiKey $key,
        Granularity $granularity,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array;

    /**
     * Get the most recent period_start for which an aggregate exists.
     */
    public function latestPeriodStart(KpiKey $key, Granularity $granularity): ?DateTimeImmutable;
}
