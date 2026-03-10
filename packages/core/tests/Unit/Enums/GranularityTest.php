<?php

declare(strict_types=1);

use Beacon\Core\Enums\Granularity;

describe('Granularity', function () {
    it('has all defined granularities', function () {
        expect(Granularity::Minute->value)->toBe('minute')
            ->and(Granularity::Hour->value)->toBe('hour')
            ->and(Granularity::Day->value)->toBe('day')
            ->and(Granularity::Week->value)->toBe('week')
            ->and(Granularity::Month->value)->toBe('month')
            ->and(Granularity::Year->value)->toBe('year');
    });

    it('identifies minute as realtime-only granularity', function () {
        expect(Granularity::Minute->isRealtimeOnly())->toBeTrue()
            ->and(Granularity::Hour->isRealtimeOnly())->toBeFalse()
            ->and(Granularity::Day->isRealtimeOnly())->toBeFalse();
    });

    it('returns default granularities without minute', function () {
        $defaults = Granularity::defaults();

        expect($defaults)->toContain(Granularity::Hour)
            ->toContain(Granularity::Day)
            ->toContain(Granularity::Week)
            ->toContain(Granularity::Month)
            ->toContain(Granularity::Year)
            ->not->toContain(Granularity::Minute);
    });

    it('returns the correct period start for a given datetime', function () {
        $datetime = new DateTimeImmutable('2024-03-15 14:37:22');

        expect(Granularity::Minute->periodStart($datetime)->format('Y-m-d H:i:s'))
            ->toBe('2024-03-15 14:37:00')
            ->and(Granularity::Hour->periodStart($datetime)->format('Y-m-d H:i:s'))
            ->toBe('2024-03-15 14:00:00')
            ->and(Granularity::Day->periodStart($datetime)->format('Y-m-d H:i:s'))
            ->toBe('2024-03-15 00:00:00')
            ->and(Granularity::Month->periodStart($datetime)->format('Y-m-d H:i:s'))
            ->toBe('2024-03-01 00:00:00')
            ->and(Granularity::Year->periodStart($datetime)->format('Y-m-d H:i:s'))
            ->toBe('2024-01-01 00:00:00');
    });

    it('returns the correct period start for ISO week', function () {
        // 2024-03-11 is a Monday (start of ISO week)
        $monday = new DateTimeImmutable('2024-03-11 09:00:00');
        $wednesday = new DateTimeImmutable('2024-03-13 14:00:00');

        expect(Granularity::Week->periodStart($monday)->format('Y-m-d'))
            ->toBe('2024-03-11')
            ->and(Granularity::Week->periodStart($wednesday)->format('Y-m-d'))
            ->toBe('2024-03-11');
    });

    it('provides human readable label', function () {
        expect(Granularity::Day->label())->toBe('Daily')
            ->and(Granularity::Hour->label())->toBe('Hourly')
            ->and(Granularity::Week->label())->toBe('Weekly')
            ->and(Granularity::Month->label())->toBe('Monthly')
            ->and(Granularity::Year->label())->toBe('Yearly')
            ->and(Granularity::Minute->label())->toBe('Per Minute');
    });
});
