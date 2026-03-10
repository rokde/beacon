<?php

declare(strict_types=1);

namespace Beacon\Recorder\Testing;

use Beacon\Core\Contracts\KpiRecorderContract;
use Beacon\Core\ValueObjects\KpiDefinition;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;

/**
 * Test double for KpiRecorderContract.
 * Swap in via KPI::fake() in tests.
 *
 * Records all record() calls in memory for assertion.
 */
final class KpiFake implements KpiRecorderContract
{
    /** @var list<array{kpiKey: string, value: float|int, meta: array<string,mixed>, recordedAt: DateTimeImmutable}> */
    private array $recorded = [];

    /** @var array<string, KpiDefinition> */
    private array $definitions = [];

    public function register(KpiDefinition $kpiDefinition): void
    {
        $this->definitions[(string) $kpiDefinition->key()] = $kpiDefinition;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(string $kpiKey, int|float $value, array $meta = []): void
    {
        $this->recorded[] = [
            'kpiKey' => $kpiKey,
            'value' => $value,
            'meta' => $meta,
            'recordedAt' => new DateTimeImmutable,
        ];
    }

    public function definitions(): array
    {
        return array_values($this->definitions);
    }

    public function definition(string $kpiKey): ?KpiDefinition
    {
        return $this->definitions[$kpiKey] ?? null;
    }

    // -------------------------------------------------------------------------
    // Assertion helpers
    // -------------------------------------------------------------------------

    public function assertRecorded(string $kpiKey, int|float $value): void
    {
        $matches = array_filter(
            $this->recorded,
            fn (array $r): bool => $r['kpiKey'] === $kpiKey && (float) $r['value'] === (float) $value,
        );

        Assert::assertNotEmpty(
            $matches,
            sprintf('Expected KPI [%s] to be recorded with value [%s], but it was not.', $kpiKey, $value),
        );
    }

    public function assertNotRecorded(string $kpiKey): void
    {
        $matches = array_filter(
            $this->recorded,
            fn (array $r): bool => $r['kpiKey'] === $kpiKey,
        );

        Assert::assertEmpty(
            $matches,
            sprintf('Expected KPI [%s] to not be recorded, but it was recorded ', $kpiKey).count($matches).' time(s).',
        );
    }

    public function assertRecordedTimes(string $kpiKey, int $times): void
    {
        $matches = array_filter(
            $this->recorded,
            fn (array $r): bool => $r['kpiKey'] === $kpiKey,
        );

        Assert::assertCount(
            $times,
            $matches,
            sprintf('Expected KPI [%s] to be recorded %d time(s), but was recorded ', $kpiKey, $times).count($matches).' time(s).',
        );
    }

    /**
     * @return list<array{kpiKey: string, value: float|int, meta: array<string,mixed>, recordedAt: DateTimeImmutable}>
     */
    public function allRecorded(): array
    {
        return $this->recorded;
    }
}
