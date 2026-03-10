<?php

declare(strict_types=1);

namespace Beacon\Recorder\Console;

use Beacon\Recorder\Jobs\AggregateKpiJob;
use Beacon\Recorder\Services\KpiRegistry;
use Illuminate\Console\Command;

final class AggregateAllKpisCommand extends Command
{
    protected $signature = 'kpi:aggregate';

    protected $description = 'Dispatch aggregation jobs for all registered KPIs (run by scheduler)';

    public function handle(KpiRegistry $registry): int
    {
        $definitions = $registry->withRecorderConfig();

        if ($definitions === []) {
            $this->warn('No KPI definitions with recorder config found.');

            return self::SUCCESS;
        }

        foreach ($definitions as $definition) {
            $queueConnection = config('kpi-recorder.queue_connection');
            $queueName = config('kpi-recorder.queue_name');
            AggregateKpiJob::dispatch((string) $definition->key())
                ->onConnection(is_string($queueConnection) ? $queueConnection : null)
                ->onQueue(is_string($queueName) ? $queueName : null);
        }

        $this->info(sprintf(
            'Dispatched aggregation jobs for %d KPI(s).',
            count($definitions),
        ));

        return self::SUCCESS;
    }
}
