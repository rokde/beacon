<?php

declare(strict_types=1);

namespace Beacon\Recorder\Facades;

use Beacon\Core\Contracts\KpiRecorderContract;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Services\KpiRecorderService;
use Beacon\Recorder\Testing\KpiFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(KpiDefinition $definition)
 * @method static void record(string $kpiKey, int|float $value, array<string, mixed> $meta = [])
 * @method static list<KpiDefinition> definitions()
 * @method static KpiDefinition|null definition(string $kpiKey)
 *
 * @see KpiRecorderService
 */
final class KPI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return KpiRecorderContract::class;
    }

    /**
     * Replace the bound implementation with a KpiFake for testing.
     * Enables KPI::assertRecorded(), KPI::assertNotRecorded(), etc.
     */
    public static function fake(): KpiFake
    {
        $kpiFake = new KpiFake;
        self::swap($kpiFake);

        return $kpiFake;
    }
}
