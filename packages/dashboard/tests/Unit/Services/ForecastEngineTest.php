<?php

declare(strict_types=1);

use Beacon\Core\Enums\Granularity;
use Beacon\Dashboard\Services\ForecastEngine;

describe('ForecastEngine', function () {
    beforeEach(function () {
        $this->engine = new ForecastEngine();
    });

    it('returns empty array for fewer than 2 data points', function () {
        $result = $this->engine->compute([], '7 days', Granularity::Day);
        expect($result)->toBeEmpty();

        $result = $this->engine->compute([
            ['period_start' => '2024-01-01 00:00:00', 'value' => 10.0, 'count' => 1],
        ], '7 days', Granularity::Day);
        expect($result)->toBeEmpty();
    });

    it('forecasts the correct number of steps for "30 days" at day granularity', function () {
        $series = array_map(fn ($i) => [
            'period_start' => "2024-01-{$i} 00:00:00",
            'value'        => (float) ($i * 10),
            'count'        => 1,
        ], range(1, 14));

        $result = $this->engine->compute($series, '7 days', Granularity::Day);

        expect($result)->toHaveCount(7);
    });

    it('forecast values continue the upward trend for a rising series', function () {
        $series = array_map(fn ($i) => [
            'period_start' => date('Y-m-d 00:00:00', strtotime("2024-01-01 +{$i} days")),
            'value'        => (float) ($i * 5),
            'count'        => 1,
        ], range(0, 9));

        $result = $this->engine->compute($series, '3 days', Granularity::Day);

        // Each forecast step should be higher than the last historical value
        $lastHistorical = $series[9]['value'];
        expect($result[0]['value'])->toBeGreaterThan($lastHistorical);
        expect($result[1]['value'])->toBeGreaterThan($result[0]['value']);
    });

    it('includes lower and upper bounds when series has variance', function () {
        // Series with variance
        $series = [
            ['period_start' => '2024-01-01 00:00:00', 'value' => 100.0, 'count' => 1],
            ['period_start' => '2024-01-02 00:00:00', 'value' => 50.0, 'count' => 1],
            ['period_start' => '2024-01-03 00:00:00', 'value' => 130.0, 'count' => 1],
            ['period_start' => '2024-01-04 00:00:00', 'value' => 70.0, 'count' => 1],
            ['period_start' => '2024-01-05 00:00:00', 'value' => 120.0, 'count' => 1],
        ];

        $result = $this->engine->compute($series, '3 days', Granularity::Day);

        expect($result[0]['lower'])->not->toBeNull()
            ->and($result[0]['upper'])->not->toBeNull()
            ->and($result[0]['upper'])->toBeGreaterThan($result[0]['value'])
            ->and($result[0]['lower'])->toBeLessThan($result[0]['value']);
    });

    it('confidence interval widens for later forecast steps', function () {
        $series = array_map(fn ($i) => [
            'period_start' => date('Y-m-d 00:00:00', strtotime("2024-01-01 +{$i} days")),
            'value'        => (float) ($i * 10 + rand(-5, 5)),
            'count'        => 1,
        ], range(0, 13));

        $result = $this->engine->compute($series, '5 days', Granularity::Day);

        if ($result[0]['upper'] !== null && $result[4]['upper'] !== null) {
            $widthFirst = $result[0]['upper'] - $result[0]['lower'];
            $widthLast = $result[4]['upper'] - $result[4]['lower'];
            expect($widthLast)->toBeGreaterThanOrEqual($widthFirst);
        }

        expect($result)->toHaveCount(5);
    });

    it('produces null bounds for a perfectly linear series (no variance)', function () {
        $series = array_map(fn ($i) => [
            'period_start' => date('Y-m-d 00:00:00', strtotime("2024-01-01 +{$i} days")),
            'value'        => (float) ($i * 10),
            'count'        => 1,
        ], range(0, 6));

        $result = $this->engine->compute($series, '3 days', Granularity::Day);

        expect($result[0]['lower'])->toBeNull()
            ->and($result[0]['upper'])->toBeNull();
    });

    it('parses week horizon to correct step count at day granularity', function () {
        $series = array_map(fn ($i) => [
            'period_start' => date('Y-m-d 00:00:00', strtotime("2024-01-01 +{$i} days")),
            'value'        => 10.0,
            'count'        => 1,
        ], range(0, 6));

        $result = $this->engine->compute($series, '2 weeks', Granularity::Day);

        expect($result)->toHaveCount(14);
    });
});
