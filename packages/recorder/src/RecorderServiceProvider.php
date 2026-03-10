<?php

declare(strict_types=1);

namespace Beacon\Recorder;

use Beacon\Core\Contracts\KpiRecorderContract;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Console\AggregateAllKpisCommand;
use Beacon\Recorder\Console\KpiInstallCommand;
use Beacon\Recorder\Console\KpiReaggregateCommand;
use Beacon\Recorder\Listeners\KpiEventListener;
use Beacon\Recorder\Services\KpiRecorderService;
use Beacon\Recorder\Services\KpiRegistry;
use Beacon\Recorder\Services\KpiWriteBuffer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class RecorderServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/kpi-recorder.php',
            'kpi-recorder',
        );

        // Singletons — both live for the duration of the request/process
        $this->app->singleton(KpiRegistry::class);
        $this->app->singleton(KpiWriteBuffer::class);

        // Bind the contract to the concrete service
        $this->app->singleton(
            KpiRecorderContract::class,
            function (): KpiRecorderContract {
                /** @var KpiRegistry $kpiRegistry */
                $kpiRegistry = $this->app->make(KpiRegistry::class);
                /** @var KpiWriteBuffer $kpiWriteBuffer */
                $kpiWriteBuffer = $this->app->make(KpiWriteBuffer::class);

                return new KpiRecorderService(
                    kpiRegistry: $kpiRegistry,
                    kpiWriteBuffer: $kpiWriteBuffer,
                    application: $this->app,
                );
            },
        );
    }

    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerEventListeners();
        $this->registerScheduler();
        $this->registerCommands();
    }

    private function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/kpi-recorder.php' => config_path('kpi-recorder.php'),
        ], 'beacon-recorder-config');

        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'beacon-recorder-migrations');
    }

    private function registerEventListeners(): void
    {
        /** @var KpiRegistry $kpiRegistry */
        $kpiRegistry = $this->app->make(KpiRegistry::class);

        // Track which (eventClass, kpiKey) pairs have already been registered
        // to avoid duplicate listeners when a definition is re-registered.
        /** @var array<string, bool> $registered */
        $registered = [];

        $app = $this->app;

        $registerListenersForDefinition = function (KpiDefinition $kpiDefinition) use ($app, &$registered): void {
            if (! $kpiDefinition->hasRecorderConfig()) {
                return;
            }

            foreach ($kpiDefinition->getEventListeners() as $eventListenerDefinition) {
                $registrationKey = $eventListenerDefinition->eventClass.'|'.$kpiDefinition->key();

                if (isset($registered[$registrationKey])) {
                    continue;
                }

                $registered[$registrationKey] = true;

                $kpiKey = (string) $kpiDefinition->key();
                $extractor = $eventListenerDefinition->extractor;

                // Resolve the recorder from the container at dispatch time so
                // that KPI::fake() (which swaps the binding) is honoured even
                // when event listeners were registered before the fake was set.
                Event::listen(
                    $eventListenerDefinition->eventClass,
                    function (object $event) use ($app, $kpiKey, $extractor): void {
                        $kpiRecorderContract = $app->make(KpiRecorderContract::class);
                        $kpiEventListener = new KpiEventListener(
                            kpiRecorderContract: $kpiRecorderContract,
                            kpiKey: $kpiKey,
                            extractor: $extractor,
                        );
                        $kpiEventListener->handle($event);
                    },
                );
            }
        };

        // Register listeners for definitions already in the registry at boot time
        foreach ($kpiRegistry->withRecorderConfig() as $kpiDefinition) {
            $registerListenersForDefinition($kpiDefinition);
        }

        // Also register listeners for definitions added after boot (e.g. in tests
        // or from service providers that boot after RecorderServiceProvider)
        $kpiRegistry->onRegister($registerListenersForDefinition);
    }

    private function registerScheduler(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $rawInterval = config('kpi-recorder.aggregation_interval', 5);
            $interval = is_int($rawInterval) ? $rawInterval : 5;

            $schedule->command(AggregateAllKpisCommand::class)
                ->cron(sprintf('*/%d * * * *', $interval))
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();
        });
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            KpiInstallCommand::class,
            AggregateAllKpisCommand::class,
            KpiReaggregateCommand::class,
        ]);
    }
}
