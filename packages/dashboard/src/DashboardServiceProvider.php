<?php

declare(strict_types=1);

namespace Beacon\Dashboard;

use Beacon\Dashboard\Http\Controllers\DashboardController;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\Services\ForecastEngine;
use Beacon\Dashboard\Services\QueryEngine;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/kpi-dashboard.php',
            'kpi-dashboard',
        );

        $this->app->singleton(DashboardRegistry::class);

        $this->app->singleton(ForecastEngine::class);

        $this->app->singleton(QueryEngine::class, function (): QueryEngine {
            /** @var ForecastEngine $forecastEngine */
            $forecastEngine = $this->app->make(ForecastEngine::class);

            return new QueryEngine(
                forecastEngine: $forecastEngine,
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'beacon-dashboard',
        );

        $this->registerComponents();
        $this->registerPublishables();
        $this->registerRoutes();
        $this->registerGates();
    }

    private function registerComponents(): void
    {
        $this->loadViewComponentsAs('beacon-dashboard', []);

        // Anonymous components live in resources/views/components/
        // Laravel auto-discovers them under the beacon-dashboard:: prefix.
    }

    private function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__.'/config/kpi-dashboard.php' => config_path('kpi-dashboard.php'),
        ], 'beacon-dashboard-config');

        // Compiled assets (dist/) — committed to git, no build step for consumers
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/beacon'),
        ], 'beacon-dashboard-assets');

        // Views — publishable for customisation
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/beacon-dashboard'),
        ], 'beacon-dashboard-views');
    }

    private function registerRoutes(): void
    {
        $rawBasePath = config('kpi-dashboard.base_path', '/kpi');
        $basePath = is_string($rawBasePath) ? $rawBasePath : '/kpi';

        // Register a single catch-all wildcard route. The controller resolves
        // the correct dashboard from the registry using the request path.
        // This avoids the need to re-register routes when dashboards are added
        // after the service provider has booted (e.g. in tests or late-booted
        // service providers).
        $this->callAfterResolving('router', function () use ($basePath): void {
            Route::middleware(['web'])
                ->prefix($basePath)
                ->group(function (): void {
                    Route::get('{path}', [DashboardController::class, 'show'])
                        ->where('path', '.*')
                        ->name('beacon.dashboard');
                });
        });
    }

    private function registerGates(): void
    {
        $this->callAfterResolving('gate', function (): void {
            $dashboardRegistry = $this->app->make(DashboardRegistry::class);

            foreach ($dashboardRegistry->all() as $dashboardDefinition) {
                Gate::define('beacon.view.'.$dashboardDefinition->id, fn (?Authenticatable $authenticatable): bool => $dashboardDefinition->isAuthorized($authenticatable));
            }
        });
    }
}
