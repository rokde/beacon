<?php

declare(strict_types=1);

namespace Beacon\Recorder\Services;

use DateTimeInterface;

/**
 * Request-scoped buffer for pending KPI writes.
 *
 * In HTTP context with a sync queue, KPI::record() pushes to this buffer
 * instead of dispatching a job immediately. The buffer is flushed in
 * KpiRecordingMiddleware::terminate() — after the response has been
 * sent to the client — so the user never waits for DB writes.
 */
final class KpiWriteBuffer
{
    /** @var list<array{kpiKey: string, value: float|int, recordedAt: DateTimeInterface, meta: array<string, mixed>}> */
    private array $pending = [];

    /**
     * @param  array<string, mixed>  $meta
     */
    public function push(string $kpiKey, int|float $value, DateTimeInterface $recordedAt, array $meta): void
    {
        $this->pending[] = compact('kpiKey', 'value', 'recordedAt', 'meta');
    }

    /**
     * @return list<array{kpiKey: string, value: float|int, recordedAt: DateTimeInterface, meta: array<string, mixed>}>
     */
    public function flush(): array
    {
        $items = $this->pending;
        $this->pending = [];

        return $items;
    }

    public function count(): int
    {
        return count($this->pending);
    }

    public function isEmpty(): bool
    {
        return $this->pending === [];
    }
}
