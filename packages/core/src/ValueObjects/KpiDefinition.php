<?php

declare(strict_types=1);

namespace Beacon\Core\ValueObjects;

use Beacon\Core\Enums\Freshness;
use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;

/**
 * Immutable value object that holds the full definition of a KPI.
 *
 * Recorder and Dashboard each consume only their relevant aspects.
 * Unknown methods (e.g. dashboard-only methods when only beacon/recorder
 * is installed) are silently ignored via __call — no fatal error, no
 * exception, full chain ability preserved.
 *
 * Every fluent setter returns a NEW instance (immutability).
 */
final class KpiDefinition
{
    /** @var list<EventListenerDefinition> */
    private array $eventListeners = [];

    /** @var list<Granularity> */
    private array $granularities;

    private function __construct(
        private readonly KpiKey $key,
        private ?KpiType $type = null,
        private int $retentionDays = 30,
        private Freshness $freshness = Freshness::Aggregate,
    ) {
        $this->granularities = Granularity::defaults();
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public static function make(KpiKey|string $key): self
    {
        return new self(
            key: $key instanceof KpiKey ? $key : KpiKey::fromString($key),
        );
    }

    // -------------------------------------------------------------------------
    // Recorder aspects — fluent setters (immutable)
    // -------------------------------------------------------------------------

    public function type(KpiType $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    /**
     * @param  list<Granularity>  $granularities
     */
    public function granularities(array $granularities): self
    {
        $clone = clone $this;
        $clone->granularities = $granularities;

        return $clone;
    }

    public function retention(int $days): self
    {
        $clone = clone $this;
        $clone->retentionDays = $days;

        return $clone;
    }

    /**
     * Register a Laravel event class with a closure that extracts
     * the numeric KPI value from the event.
     *
     * Can be called multiple times to register multiple listeners.
     *
     * @param  class-string  $eventClass
     * @param  callable(object): (float|int)  $extractor
     */
    public function listenOn(string $eventClass, callable $extractor): self
    {
        $clone = clone $this;
        $clone->eventListeners = [
            ...$this->eventListeners,
            new EventListenerDefinition($eventClass, $extractor),
        ];

        return $clone;
    }

    public function freshness(Freshness $freshness): self
    {
        $clone = clone $this;
        $clone->freshness = $freshness;

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function key(): KpiKey
    {
        return $this->key;
    }

    public function getType(): ?KpiType
    {
        return $this->type;
    }

    /**
     * @return list<Granularity>
     */
    public function getGranularities(): array
    {
        return $this->granularities;
    }

    public function getRetentionDays(): int
    {
        return $this->retentionDays;
    }

    /**
     * @return list<EventListenerDefinition>
     */
    public function getEventListeners(): array
    {
        return $this->eventListeners;
    }

    public function getFreshness(): Freshness
    {
        return $this->freshness;
    }

    /**
     * A definition has recorder config when at least a KpiType is set.
     */
    public function hasRecorderConfig(): bool
    {
        return $this->type !== null;
    }

    // -------------------------------------------------------------------------
    // Graceful unknown-method handling
    //
    // Dashboard-only methods (->label(), ->showForecast(), ->comparison(), ...)
    // are silently ignored when only beacon/core or beacon/recorder is installed.
    // This allows SharedDefinition files to be written once for both packages
    // without causing fatal errors in partial installations.
    // -------------------------------------------------------------------------

    /**
     * @param  array<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        // Silently ignore unknown methods, return self for chaining
        return $this;
    }

    // -------------------------------------------------------------------------
    // Clone support — ensure eventListeners array is deep-copied
    // (closures are not cloned, but that is intentional — they are shared)
    // -------------------------------------------------------------------------

    public function __clone()
    {
        // eventListeners are readonly value objects — shallow copy of the
        // array is sufficient since EventListenerDefinition is immutable.
    }
}
