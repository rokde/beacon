<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Tests;

use Beacon\Dashboard\DashboardServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DashboardServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'kpi');
        $app['config']->set('database.connections.kpi', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('kpi-dashboard.connection', 'kpi');
        $app['config']->set('kpi-dashboard.base_path', '/kpi');
        $app['config']->set('kpi-dashboard.refresh_interval', 300);

        // Required by the web middleware group (session encryption)
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/../../recorder/src/database/migrations',
        );
    }
}
