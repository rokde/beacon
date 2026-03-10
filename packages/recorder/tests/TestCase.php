<?php

declare(strict_types=1);

namespace Beacon\Recorder\Tests;

use Beacon\Recorder\RecorderServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Simulate running inside an HTTP request context.
     *
     * In Orchestra TestBench, tests run from CLI so app->runningInConsole()
     * returns true by default. This method overrides that so code paths
     * that branch on HTTP vs CLI context can be tested.
     */
    protected function simulateHttpContext(): void
    {
        // Access the protected property via Closure binding
        (function (): void {
            $this->isRunningInConsole = false;
        })->call($this->app);
    }

    protected function getPackageProviders($app): array
    {
        return [
            RecorderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'kpi');
        $app['config']->set('database.connections.kpi', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('kpi-recorder.connection', 'kpi');
        $app['config']->set('kpi-recorder.queue_connection', 'sync');
        $app['config']->set('kpi-recorder.queue_name', 'kpi');
        $app['config']->set('kpi-recorder.aggregation_interval', 5);
        $app['config']->set('kpi-recorder.retention_days', 30);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../src/database/migrations',
        );
    }
}
