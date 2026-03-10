<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Services;

use Beacon\Core\Enums\Freshness;
use Beacon\Core\Enums\Granularity;
use Beacon\Dashboard\ValueObjects\TileDefinition;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reads aggregated KPI data from kpi_aggregates (or kpi_events for realtime),
 * computes comparisons, applies forecast, and returns a structured array
 * ready for Blade rendering.
 */
final readonly class QueryEngine
{
    public function __construct(
        private ForecastEngine $forecastEngine,
    ) {}

    /**
     * @return array{
     *     current_value: float,
     *     previous_value: float|null,
     *     trend_direction: 'down'|'neutral'|'up',
     *     trend_pct: float|null,
     *     comparison_label: string|null,
     *     series: list<array{period_start: string, value: float, count: int}>,
     *     forecast: list<array{date: string, value: float, lower: float|null, upper: float|null}>,
     *     is_realtime: bool,
     * }
     */
    public function queryForTile(TileDefinition $tileDefinition, Freshness $freshness): array
    {
        $rawConnection = config('kpi-dashboard.connection', 'kpi');
        $connection = is_string($rawConnection) ? $rawConnection : 'kpi';
        $cacheKey = sprintf('beacon.tile.%s.%s.%d', $tileDefinition->kpiKey, $tileDefinition->getGranularity()->value, $tileDefinition->getPeriodDays());
        $rawInterval = config('kpi-recorder.aggregation_interval', 5);
        $cacheTtl = $freshness->isRealtime() ? 0 : (is_int($rawInterval) ? $rawInterval : 5) * 60;

        $query = fn (): array => $this->executeQuery($tileDefinition, $connection, $freshness);

        if ($cacheTtl > 0) {
            /** @var array{current_value: float, previous_value: float|null, trend_direction: 'down'|'neutral'|'up', trend_pct: float|null, comparison_label: string|null, series: list<array{period_start: string, value: float, count: int}>, forecast: list<array{date: string, value: float, lower: float|null, upper: float|null}>, is_realtime: bool} $result */
            $result = Cache::remember($cacheKey, $cacheTtl, $query);

            return $result;
        }

        return $query();
    }

    /**
     * @return array{current_value: float, previous_value: float|null, trend_direction: 'down'|'neutral'|'up', trend_pct: float|null, comparison_label: string|null, series: list<array{period_start: string, value: float, count: int}>, forecast: list<array{date: string, value: float, lower: float|null, upper: float|null}>, is_realtime: bool}
     */
    private function executeQuery(TileDefinition $tileDefinition, string $connection, Freshness $freshness): array
    {
        $now = new DateTimeImmutable;
        $from = $now->modify(sprintf('-%d days', $tileDefinition->getPeriodDays()));

        $series = $this->fetchSeries($tileDefinition->kpiKey, $tileDefinition->getGranularity(), $from, $now, $connection);

        $currentValue = (float) collect($series)->sum('value');
        $previousValue = null;
        $comparisonLabel = null;

        // First comparison drives the trend indicator
        $firstComparison = $tileDefinition->getComparisons()[0] ?? null;

        if ($firstComparison !== null) {
            ['from' => $compFrom, 'to' => $compTo] = $firstComparison->resolve($from, $now);
            $prevSeries = $this->fetchSeries($tileDefinition->kpiKey, $tileDefinition->getGranularity(), $compFrom, $compTo, $connection);
            $previousValue = (float) collect($prevSeries)->sum('value');
            $comparisonLabel = $firstComparison->label;
        }

        $trendPct = null;

        if ($previousValue !== null && abs($previousValue) > PHP_FLOAT_EPSILON) {
            $trendPct = round((($currentValue - $previousValue) / $previousValue) * 100, 1);
        }

        $trendDirection = match (true) {
            $trendPct === null => 'neutral',
            $trendPct > 0 => 'up',
            $trendPct < 0 => 'down',
            default => 'neutral',
        };

        $forecastPoints = [];

        if ($tileDefinition->hasForecast()) {
            $forecastPoints = $this->forecastEngine->compute(
                $series,
                $tileDefinition->getForecastHorizon(),
                $tileDefinition->getGranularity(),
            );
        }

        return [
            'current_value' => $currentValue,
            'previous_value' => $previousValue,
            'trend_direction' => $trendDirection,
            'trend_pct' => $trendPct,
            'comparison_label' => $comparisonLabel,
            'series' => $series,
            'forecast' => $forecastPoints,
            'is_realtime' => $freshness->isRealtime(),
        ];
    }

    /**
     * @return list<array{period_start: string, value: float, count: int}>
     */
    private function fetchSeries(
        string $kpiKey,
        Granularity $granularity,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        string $connection,
    ): array {
        $rows = DB::connection($connection)
            ->table('kpi_aggregates')
            ->where('kpi_key', $kpiKey)
            ->where('granularity', $granularity->value)
            ->whereBetween('period_start', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ])
            ->orderBy('period_start')
            ->get(['period_start', 'value', 'count']);

        /** @var list<array{period_start: string, value: float, count: int}> $result */
        $result = array_values($rows->map(fn (object $row): array => [
            'period_start' => (string) $row->period_start,  // @phpstan-ignore-line
            'value' => (float) $row->value,           // @phpstan-ignore-line
            'count' => (int) $row->count,             // @phpstan-ignore-line
        ])->all());

        return $result;
    }
}
