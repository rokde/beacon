<?php

declare(strict_types=1);

use Beacon\Dashboard\ValueObjects\Comparison;

describe('Comparison', function () {
    it('previous period shifts the window by its own duration', function () {
        $from = new DateTimeImmutable('2024-06-01 00:00:00');
        $to = new DateTimeImmutable('2024-06-30 23:59:59');

        $comparison = Comparison::previousPeriod();
        ['from' => $compFrom, 'to' => $compTo] = $comparison->resolve($from, $to);

        // Duration is 29 days + 23:59:59 ≈ 30 days; previous period starts ~May 2
        expect($compFrom)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($compFrom->getTimestamp())->toBeLessThan($from->getTimestamp())
            ->and($compTo->getTimestamp())->toBeLessThan($to->getTimestamp());
    });

    it('offset shifts by the given modifier', function () {
        $from = new DateTimeImmutable('2024-06-08 00:00:00');
        $to = new DateTimeImmutable('2024-06-14 23:59:59');

        $comparison = Comparison::offset('-1 week');
        ['from' => $compFrom, 'to' => $compTo] = $comparison->resolve($from, $to);

        expect($compFrom->format('Y-m-d'))->toBe('2024-06-01')
            ->and($compTo->format('Y-m-d'))->toBe('2024-06-07');
    });

    it('offset with explicit window sets a fixed-size comparison window', function () {
        $from = new DateTimeImmutable('2024-06-10 00:00:00');
        $to = new DateTimeImmutable('2024-06-12 23:59:59');

        $comparison = Comparison::offset('-3 days', '3 days');
        ['from' => $compFrom, 'to' => $compTo] = $comparison->resolve($from, $to);

        expect($compFrom->format('Y-m-d'))->toBe('2024-06-07');

        $windowSeconds = $compTo->getTimestamp() - $compFrom->getTimestamp();
        expect($windowSeconds)->toBe(3 * 24 * 60 * 60); // exactly 3 days
    });

    it('has a human-readable label', function () {
        expect(Comparison::previousPeriod()->label)->toBe('vs. previous period')
            ->and(Comparison::offset('-1 week')->label)->toBe('vs. -1 week');
    });
});
