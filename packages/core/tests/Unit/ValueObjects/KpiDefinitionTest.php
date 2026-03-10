<?php

declare(strict_types=1);

use Beacon\Core\Enums\Freshness;
use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Core\ValueObjects\KpiKey;

describe('KpiDefinition', function (): void {
    it('can be created via make() with a string key', function (): void {
        $definition = KpiDefinition::make('orders_count');

        expect((string) $definition->key())->toBe('orders_count');
    });

    it('can be created via make() with a KpiKey instance', function (): void {
        $key = KpiKey::fromString('revenue_total');
        $definition = KpiDefinition::make($key);

        expect($definition->key())->toBe($key);
    });

    it('defaults to null type', function (): void {
        $definition = KpiDefinition::make('key');

        expect($definition->getType())->toBeNull();
    });

    it('defaults to 30 retention days', function (): void {
        $definition = KpiDefinition::make('key');

        expect($definition->getRetentionDays())->toBe(30);
    });

    it('defaults to aggregate freshness', function (): void {
        $definition = KpiDefinition::make('key');

        expect($definition->getFreshness())->toBe(Freshness::Aggregate);
    });

    it('defaults to standard granularities without Minute', function (): void {
        $definition = KpiDefinition::make('key');

        expect($definition->getGranularities())->not->toContain(Granularity::Minute);
        expect($definition->getGranularities())->toContain(Granularity::Day);
    });

    it('defaults to no event listeners', function (): void {
        $definition = KpiDefinition::make('key');

        expect($definition->getEventListeners())->toBeEmpty();
    });

    it('returns false for hasRecorderConfig when type is not set', function (): void {
        $definition = KpiDefinition::make('key');

        expect($definition->hasRecorderConfig())->toBeFalse();
    });

    it('returns true for hasRecorderConfig when type is set', function (): void {
        $definition = KpiDefinition::make('key')->type(KpiType::SimpleCounter);

        expect($definition->hasRecorderConfig())->toBeTrue();
    });

    it('is immutable — type() returns a new instance', function (): void {
        $original = KpiDefinition::make('key');
        $modified = $original->type(KpiType::Gauge);

        expect($modified)->not->toBe($original);
        expect($original->getType())->toBeNull();
        expect($modified->getType())->toBe(KpiType::Gauge);
    });

    it('is immutable — granularities() returns a new instance', function (): void {
        $original = KpiDefinition::make('key');
        $modified = $original->granularities([Granularity::Hour, Granularity::Day]);

        expect($modified)->not->toBe($original);
        expect($modified->getGranularities())->toBe([Granularity::Hour, Granularity::Day]);
    });

    it('is immutable — retention() returns a new instance', function (): void {
        $original = KpiDefinition::make('key');
        $modified = $original->retention(90);

        expect($modified)->not->toBe($original);
        expect($original->getRetentionDays())->toBe(30);
        expect($modified->getRetentionDays())->toBe(90);
    });

    it('is immutable — freshness() returns a new instance', function (): void {
        $original = KpiDefinition::make('key');
        $modified = $original->freshness(Freshness::Realtime);

        expect($modified)->not->toBe($original);
        expect($original->getFreshness())->toBe(Freshness::Aggregate);
        expect($modified->getFreshness())->toBe(Freshness::Realtime);
    });

    it('registers event listeners via listenOn()', function (): void {
        $extractor = fn (object $event): float => 1.0;
        $definition = KpiDefinition::make('key')->listenOn('App\\Events\\OrderPlaced', $extractor);

        expect($definition->getEventListeners())->toHaveCount(1);
        expect($definition->getEventListeners()[0]->eventClass)->toBe('App\\Events\\OrderPlaced');
    });

    it('is immutable — listenOn() appends without mutating original', function (): void {
        $extractor = fn (object $event): float => 1.0;
        $original = KpiDefinition::make('key');
        $modified = $original->listenOn('App\\Events\\OrderPlaced', $extractor);

        expect($original->getEventListeners())->toBeEmpty();
        expect($modified->getEventListeners())->toHaveCount(1);
    });

    it('can accumulate multiple event listeners', function (): void {
        $extractor = fn (object $event): float => 1.0;
        $definition = KpiDefinition::make('key')
            ->listenOn('App\\Events\\OrderPlaced', $extractor)
            ->listenOn('App\\Events\\OrderCompleted', $extractor);

        expect($definition->getEventListeners())->toHaveCount(2);
    });

    it('silently ignores unknown methods via __call', function (): void {
        $definition = KpiDefinition::make('key')
            ->label('My Label')
            ->showForecast('30 days');

        expect($definition)->toBeInstanceOf(KpiDefinition::class);
        expect((string) $definition->key())->toBe('key');
    });
});
