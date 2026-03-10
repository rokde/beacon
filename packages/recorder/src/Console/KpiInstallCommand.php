<?php

declare(strict_types=1);

namespace Beacon\Recorder\Console;

use Illuminate\Console\Command;

final class KpiInstallCommand extends Command
{
    protected $signature = 'kpi:install
        {--recorder : Install only recorder config and migrations}
        {--force : Overwrite existing config files}';

    protected $description = 'Install the Beacon KPI Recorder (publishes config and migrations)';

    public function handle(): int
    {
        $this->info('Installing Beacon KPI Recorder...');

        $this->callSilent('vendor:publish', [
            '--tag'   => 'beacon-recorder-config',
            '--force' => $this->option('force'),
        ]);

        $this->callSilent('vendor:publish', [
            '--tag'   => 'beacon-recorder-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->info('✓ Config published  → config/kpi-recorder.php');
        $this->info('✓ Migrations published → database/migrations/');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Add your KPI_DB_CONNECTION to .env');
        $this->line('  2. Run: php artisan migrate');
        $this->line('  3. Register KpiDefinitions in your KpiServiceProvider');

        return self::SUCCESS;
    }
}
