<?php

declare(strict_types=1);

use Beacon\Core\Enums\Granularity;
use Beacon\Dashboard\Enums\TileSize;
use Beacon\Dashboard\Tile;
use Beacon\Dashboard\ValueObjects\Comparison;
use Beacon\Dashboard\ValueObjects\TileDefinition;

describe('TileDefinition', function () {
    it('can be created via Tile::kpi() shorthand', function () {
        $tile = Tile::kpi('new_registrations');

        expect($tile)->toBeInstanceOf(TileDefinition::class)
            ->and($tile->kpiKey)->toBe('new_registrations');
    });

    it('defaults to medium size', function () {
        $tile = Tile::kpi('test');
        expect($tile->getSize())->toBe(TileSize::Medium);
    });

    it('defaults to day granularity', function () {
        $tile = Tile::kpi('test');
        expect($tile->getGranularity())->toBe(Granularity::Day);
    });

    it('defaults to 30-day period', function () {
        $tile = Tile::kpi('test');
        expect($tile->getPeriodDays())->toBe(30);
    });

    it('has no forecast by default', function () {
        $tile = Tile::kpi('test');
        expect($tile->hasForecast())->toBeFalse();
    });

    it('is immutable — fluent setters return new instances', function () {
        $original = Tile::kpi('test');
        $modified = $original->label('My KPI');

        expect($original)->not->toBe($modified);
    });

    it('can set label', function () {
        $tile = Tile::kpi('test')->label('My Label');
        expect($tile->getLabel())->toBe('My Label');
    });

    it('can set size', function () {
        $tile = Tile::kpi('test')->size(TileSize::Large);
        expect($tile->getSize())->toBe(TileSize::Large);
    });

    it('can set comparison', function () {
        $comparison = Comparison::previousPeriod();
        $tile = Tile::kpi('test')->comparison($comparison);

        expect($tile->getComparisons())->toHaveCount(1)
            ->and($tile->getComparisons()[0])->toBe($comparison);
    });

    it('can set multiple comparisons', function () {
        $tile = Tile::kpi('test')->comparisons([
            Comparison::previousPeriod(),
            Comparison::offset('-1 week'),
        ]);

        expect($tile->getComparisons())->toHaveCount(2);
    });

    it('can enable forecast with custom horizon', function () {
        $tile = Tile::kpi('test')->showForecast(horizon: '14 days');

        expect($tile->hasForecast())->toBeTrue()
            ->and($tile->getForecastHorizon())->toBe('14 days');
    });

    it('can set chart type and height', function () {
        $tile = Tile::kpi('test')->chart('bar', 300);

        expect($tile->getChartType())->toBe('bar')
            ->and($tile->getChartHeight())->toBe(300);
    });
});
