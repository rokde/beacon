<?php

declare(strict_types=1);

namespace Beacon\Recorder\Console;

use Beacon\Core\ValueObjects\KpiDefinition;
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

    public function handle(KpiRegistry $kpiRegistry): int
    {
        $kpiKey = $this->argument('kpi');
        $definition = $kpiRegistry->get($kpiKey);

        if (! $definition instanceof KpiDefinition) {
            $this->error(sprintf('KPI [%s] is not registered.', $kpiKey));

            return self::FAILURE;
        }

        if ($this->option('sync')) {
            $this->info(sprintf('Re-aggregating [%s] synchronously...', $kpiKey));
            new AggregateKpiJob($kpiKey)->handle($kpiRegistry);
            $this->info('✓ Done.');

            return self::SUCCESS;
        }

        $queueConnection = config('kpi-recorder.queue_connection');
        $queueName = config('kpi-recorder.queue_name');
        AggregateKpiJob::dispatch($kpiKey)
            ->onConnection(is_string($queueConnection) ? $queueConnection : null)
            ->onQueue(is_string($queueName) ? $queueName : null);

        $this->info(sprintf('Dispatched re-aggregation job for [%s].', $kpiKey));

        return self::SUCCESS;
    }
}
