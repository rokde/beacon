<?php

declare(strict_types=1);

namespace Beacon\Core\Contracts;

use Beacon\Core\ValueObjects\KpiDefinition;

interface KpiRecorderContract
{
    /**
     * Register a KpiDefinition for tracking.
     * Called from the host app's KpiServiceProvider.
     */
    public function register(KpiDefinition $definition): void;

    /**
     * Record a KPI value directly (Option A — explicit call).
     *
     * Depending on the runtime context this either:
     * - Enqueues the write to a TerminableMiddleware buffer (HTTP context, sync queue)
     * - Dispatches a RecordKpiEventJob to the queue (non-HTTP or async queue)
     *
     * @param  array<string, mixed>  $meta
     */
    public function record(string $kpiKey, int|float $value, array $meta = []): void;

    /**
     * Return all registered definitions.
     *
     * @return list<KpiDefinition>
     */
    public function definitions(): array;

    /**
     * Retrieve a single registered definition by key.
     */
    public function definition(string $kpiKey): ?KpiDefinition;
}
