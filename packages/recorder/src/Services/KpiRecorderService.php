<?php

declare(strict_types=1);

namespace Beacon\Recorder\Services;

use Beacon\Core\Contracts\KpiRecorderContract;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Jobs\RecordKpiEventJob;
use DateTimeImmutable;
use Illuminate\Contracts\Foundation\Application;

/**
 * Main implementation of KpiRecorderContract.
 *
 * Routing logic for record():
 *
 *  ┌─────────────────────────────┬──────────────────┬─────────────────────────┐
 *  │ Context                     │ Queue driver     │ Action                  │
 *  ├─────────────────────────────┼──────────────────┼─────────────────────────┤
 *  │ HTTP request                │ sync             │ Push to KpiWriteBuffer  │
 *  │                             │                  │ (flush in terminate())  │
 *  │ HTTP request                │ redis/database/… │ Dispatch job directly   │
 *  │ CLI / Queue worker / Test   │ any              │ Dispatch job directly   │
 *  └─────────────────────────────┴──────────────────┴─────────────────────────┘
 */
final class KpiRecorderService implements KpiRecorderContract
{
    public function __construct(
        private readonly KpiRegistry $registry,
        private readonly KpiWriteBuffer $buffer,
        private readonly Application $app,
    ) {
    }

    public function register(KpiDefinition $definition): void
    {
        $this->registry->register($definition);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function record(string $kpiKey, int | float $value, array $meta = []): void
    {
        $recordedAt = new DateTimeImmutable();

        if ($this->shouldBuffer()) {
            $this->buffer->push($kpiKey, $value, $recordedAt, $meta);

            return;
        }

        $queueConnection = config('kpi-recorder.queue_connection');
        $queueName = config('kpi-recorder.queue_name');
        RecordKpiEventJob::dispatch($kpiKey, $value, $recordedAt, $meta)
            ->onConnection(is_string($queueConnection) ? $queueConnection : null)
            ->onQueue(is_string($queueName) ? $queueName : null);
    }

    public function definitions(): array
    {
        return $this->registry->all();
    }

    public function definition(string $kpiKey): ?KpiDefinition
    {
        return $this->registry->get($kpiKey);
    }

    /**
     * Returns true when writes should be buffered for post-response flushing.
     *
     * Conditions that must ALL be true:
     *  1. Running inside an HTTP request (not CLI / queue worker)
     *  2. The configured queue connection is "sync" (no real async queue)
     */
    private function shouldBuffer(): bool
    {
        return $this->isHttpContext()
            && $this->isSyncQueue();
    }

    private function isHttpContext(): bool
    {
        return $this->app->runningInConsole() === false;
    }

    private function isSyncQueue(): bool
    {
        return config('kpi-recorder.queue_connection') === 'sync';
    }
}
