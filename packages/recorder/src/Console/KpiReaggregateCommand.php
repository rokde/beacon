<?php

declare(strict_types=1);

namespace Beacon\Recorder\Console;

use Beacon\Recorder\Jobs\AggregateKpiJob;
use Beacon\Recorder\Services\KpiRegistry;
use Illuminate\Console\Command;

final class KpiReaggregateCommand extends Command
{
    protected $signature = 'kpi:reaggregate
        {kpi : The KPI key to re-aggregate}
        {--date= : Optional date (Y-m-d) to limit scope, defaults to all available data}
        {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Manually re-aggregate a KPI (use after queue outage or data correction)';

    public function handle(KpiRegistry $registry): int
    {
        $kpiKey = $this->argument('kpi');
        $definition = $registry->get($kpiKey);

        if ($definition === null) {
            $this->error("KPI [{$kpiKey}] is not registered.");

            return self::FAILURE;
        }

        if ($this->option('sync')) {
            $this->info("Re-aggregating [{$kpiKey}] synchronously...");
            (new AggregateKpiJob($kpiKey))->handle($registry);
            $this->info('✓ Done.');

            return self::SUCCESS;
        }

        $queueConnection = config('kpi-recorder.queue_connection');
        $queueName = config('kpi-recorder.queue_name');
        AggregateKpiJob::dispatch($kpiKey)
            ->onConnection(is_string($queueConnection) ? $queueConnection : null)
            ->onQueue(is_string($queueName) ? $queueName : null);

        $this->info("Dispatched re-aggregation job for [{$kpiKey}].");

        return self::SUCCESS;
    }
}
