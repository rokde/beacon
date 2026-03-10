<?php

declare(strict_types=1);

namespace Beacon\Recorder\Services;

use Beacon\Core\ValueObjects\KpiDefinition;

/**
 * In-memory registry of all registered KpiDefinitions.
 * Populated during RecorderServiceProvider::boot() from the host app's
 * KpiServiceProvider. Lives as a singleton in the container.
 */
final class KpiRegistry
{
    /** @var array<string, KpiDefinition> keyed by kpi_key string */
    private array $definitions = [];

    /**
     * Callbacks invoked whenever a definition is (re-)registered.
     *
     * @var list<callable(KpiDefinition): void>
     */
    private array $onRegisterCallbacks = [];

    /**
     * Register a callback that is called each time a KpiDefinition is registered.
     * Used by RecorderServiceProvider to hook in event-listener registration so
     * definitions added after boot() (e.g. in tests) still get listeners.
     *
     * @param callable(KpiDefinition): void $callback
     */
    public function onRegister(callable $callback): void
    {
        $this->onRegisterCallbacks[] = $callback;
    }

    public function register(KpiDefinition $definition): void
    {
        $this->definitions[(string) $definition->key()] = $definition;

        foreach ($this->onRegisterCallbacks as $callback) {
            $callback($definition);
        }
    }

    public function get(string $key): ?KpiDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * @return list<KpiDefinition>
     */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    /**
     * Returns only definitions that have recorder configuration (type is set).
     * Filters out dashboard-only definitions that may have been registered
     * via SharedDefinition without a KpiType.
     *
     * @return list<KpiDefinition>
     */
    public function withRecorderConfig(): array
    {
        return array_values(
            array_filter(
                $this->definitions,
                fn (KpiDefinition $d) => $d->hasRecorderConfig(),
            ),
        );
    }
}
