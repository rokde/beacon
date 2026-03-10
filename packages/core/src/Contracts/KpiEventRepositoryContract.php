<?php

declare(strict_types=1);

namespace Beacon\Core\Contracts;

use Beacon\Core\ValueObjects\KpiKey;
use DateTimeImmutable;

interface KpiEventRepositoryContract
{
    /**
     * Persist a raw KPI event to kpi_events.
     *
     * @param  array<string, mixed>  $meta
     */
    public function insert(KpiKey $key, int|float $value, DateTimeImmutable $recordedAt, array $meta = []): void;

    /**
     * Fetch all unprocessed events for a given KPI since a given timestamp.
     *
     * @return list<object{kpi_key: string, value: float, recorded_at: string, meta: string}>
     */
    public function unprocessedSince(KpiKey $key, DateTimeImmutable $since): array;

    /**
     * Delete events older than the given datetime for a KPI.
     */
    public function deleteOlderThan(KpiKey $key, DateTimeImmutable $before): int;
}
