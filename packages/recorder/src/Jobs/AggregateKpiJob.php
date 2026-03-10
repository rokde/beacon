<?php

declare(strict_types=1);

namespace Beacon\Recorder\Jobs;

use Beacon\Core\Enums\Granularity;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Services\KpiRegistry;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Reads unprocessed kpi_events for a single KPI, computes aggregates for
 * all configured granularities, upserts into kpi_aggregates, then purges
 * raw events that exceed the retention policy.
 *
 * Design constraints:
 * - Idempotent: upsert ensures no duplicates even if run multiple times
 * - Parallelisable: one job per KPI — can run concurrently on the queue
 * - Retention: deletes raw events older than definition->retentionDays()
 */
final class AggregateKpiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $kpiKey,
    ) {
    }

    public function handle(KpiRegistry $registry): void
    {
        $definition = $registry->get($this->kpiKey);

        if ($definition === null) {
            return;
        }

        $rawConnection = config('kpi-recorder.connection', 'kpi');
        $connection = is_string($rawConnection) ? $rawConnection : 'kpi';
        $db = DB::connection($connection);

        /** @var Collection<int, stdClass> $events */
        $events = $db->table('kpi_events')
            ->where('kpi_key', $this->kpiKey)
            ->orderBy('recorded_at')
            ->get(['value', 'recorded_at']);

        if ($events->isEmpty()) {
            return;
        }

        foreach ($definition->getGranularities() as $granularity) {
            $this->aggregateForGranularity($db, $events, $granularity);
        }

        $this->enforceRetention($db, $definition);
    }

    /**
     * @param Collection<int, stdClass> $events
     */
    private function aggregateForGranularity(
        \Illuminate\Database\Connection $db,
        Collection $events,
        Granularity $granularity,
    ): void {
        // Group events by their period_start for this granularity
        $grouped = $events->groupBy(function (object $event) use ($granularity): string {
            $dt = new DateTimeImmutable((string) $event->recorded_at); // @phpstan-ignore-line

            return $granularity->periodStart($dt)->format('Y-m-d H:i:s');
        });

        foreach ($grouped as $periodStart => $periodEvents) {
            $value = $periodEvents->sum('value');
            $count = $periodEvents->count();

            // Upsert — idempotent: update if exists, insert if not
            $existing = $db->table('kpi_aggregates')
                ->where('kpi_key', $this->kpiKey)
                ->where('granularity', $granularity->value)
                ->where('period_start', $periodStart)
                ->first();

            if ($existing !== null) {
                $db->table('kpi_aggregates')
                    ->where('kpi_key', $this->kpiKey)
                    ->where('granularity', $granularity->value)
                    ->where('period_start', $periodStart)
                    ->update([
                        'value'      => $value,
                        'count'      => $count,
                        'updated_at' => now()->toDateTimeString(),
                    ]);
            } else {
                $db->table('kpi_aggregates')->insert([
                    'kpi_key'      => $this->kpiKey,
                    'granularity'  => $granularity->value,
                    'period_start' => $periodStart,
                    'value'        => $value,
                    'count'        => $count,
                    'meta'         => '{}',
                    'created_at'   => now()->toDateTimeString(),
                    'updated_at'   => now()->toDateTimeString(),
                ]);
            }
        }
    }

    private function enforceRetention(
        \Illuminate\Database\Connection $db,
        KpiDefinition $definition,
    ): void {
        $cutoff = now()->subDays($definition->getRetentionDays())->toDateTimeString();

        $db->table('kpi_events')
            ->where('kpi_key', $this->kpiKey)
            ->where('recorded_at', '<', $cutoff)
            ->delete();
    }
}
