<?php

declare(strict_types=1);

namespace Beacon\Recorder\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Writes a single raw KPI event into kpi_events.
 *
 * This job is either:
 * - Dispatched immediately (async queue or non-HTTP context)
 * - Dispatched in batch from KpiRecordingMiddleware::terminate() (sync queue + HTTP)
 */
final class RecordKpiEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $kpiKey,
        public readonly int | float $value,
        public readonly DateTimeInterface $recordedAt,
        /** @var array<string, mixed> */
        public readonly array $meta,
    ) {
    }

    public function handle(): void
    {
        $rawConnection = config('kpi-recorder.connection', 'kpi');
        DB::connection(is_string($rawConnection) ? $rawConnection : 'kpi')
            ->table('kpi_events')
            ->insert([
                'kpi_key'     => $this->kpiKey,
                'value'       => $this->value,
                'recorded_at' => $this->recordedAt->format('Y-m-d H:i:s'),
                'meta'        => json_encode($this->meta, JSON_THROW_ON_ERROR),
            ]);
    }
}
