<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Services;

use Beacon\Core\Enums\Granularity;
use DateTimeImmutable;

/**
 * Computes a linear regression forecast over historical aggregate series.
 *
 * Returns forecast points with optional 90% confidence interval bounds
 * (lower/upper) based on historical variance (residuals).
 */
final class ForecastEngine
{
    /**
     * @param  list<array{period_start: string, value: float, count: int}>  $series
     * @param  string  $horizon  e.g. '30 days', '7 days', '12 weeks'
     * @return list<array{date: string, value: float, lower: float|null, upper: float|null}>
     */
    public function compute(array $series, string $horizon, Granularity $granularity): array
    {
        if (count($series) < 2) {
            return [];
        }

        $n = count($series);
        $xs = range(0, $n - 1);
        $ys = array_column($series, 'value');

        [$slope, $intercept] = $this->linearRegression($xs, $ys);

        // Compute residual standard deviation for confidence intervals
        $residuals = array_map(
            fn (int $i, float $y) => $y - ($slope * $i + $intercept),
            $xs,
            $ys,
        );
        $residualStd = $this->standardDeviation($residuals);

        // Z-score for ~90% confidence interval
        $z = 1.645;

        // Parse horizon into number of forecast steps
        $steps = $this->horizonToSteps($horizon, $granularity);

        $lastDate = new DateTimeImmutable($series[$n - 1]['period_start']);
        $stepModifier = $this->granularityToModifier($granularity);

        $points = [];

        for ($i = 1; $i <= $steps; $i++) {
            $x = $n - 1 + $i;
            $value = $slope * $x + $intercept;
            $date = $lastDate->modify("+{$i} {$stepModifier}");

            $lower = $residualStd > 0 ? $value - $z * $residualStd * sqrt($i) : null;
            $upper = $residualStd > 0 ? $value + $z * $residualStd * sqrt($i) : null;

            $points[] = [
                'date' => $date->format('Y-m-d H:i:s'),
                'value' => round($value, 4),
                'lower' => $lower !== null ? round($lower, 4) : null,
                'upper' => $upper !== null ? round($upper, 4) : null,
            ];
        }

        return $points;
    }

    /**
     * @param  list<int>  $xs
     * @param  list<float>  $ys
     * @return array{float, float} [slope, intercept]
     */
    private function linearRegression(array $xs, array $ys): array
    {
        $n = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXY = 0.0;
        $sumX2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $ys[$i];
            $sumX2 += $xs[$i] ** 2;
        }

        $denom = ($n * $sumX2 - $sumX ** 2);
        $slope = $denom !== 0.0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0.0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [$slope, $intercept];
    }

    /** @param list<float> $values */
    private function standardDeviation(array $values): float
    {
        $n = count($values);

        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(fn (float $v): float => ($v - $mean) ** 2, $values)) / ($n - 1);

        return sqrt($variance);
    }

    private function horizonToSteps(string $horizon, Granularity $granularity): int
    {
        preg_match('/^(\d+)\s+(\w+)$/', $horizon, $m);
        $count = isset($m[1]) ? (int) $m[1] : 30;
        $unit = strtolower($m[2] ?? 'days');

        // Normalise to the granularity's unit
        return match ($granularity) {
            Granularity::Day => match ($unit) {
                'day', 'days' => $count,
                'week', 'weeks' => $count * 7,
                'month','months' => $count * 30,
                default => $count,
            },
            Granularity::Hour => match ($unit) {
                'hour', 'hours' => $count,
                'day', 'days' => $count * 24,
                default => $count,
            },
            Granularity::Week => (int) ceil($count / 7),
            Granularity::Month => (int) ceil($count / 30),
            default => $count,
        };
    }

    private function granularityToModifier(Granularity $granularity): string
    {
        return match ($granularity) {
            Granularity::Minute => 'minutes',
            Granularity::Hour => 'hours',
            Granularity::Day => 'days',
            Granularity::Week => 'weeks',
            Granularity::Month => 'months',
            Granularity::Year => 'years',
        };
    }
}
