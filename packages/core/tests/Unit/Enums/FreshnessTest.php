<?php

declare(strict_types=1);

use Beacon\Core\Enums\Freshness;

describe('Freshness', function () {
    it('has aggregate and realtime modes', function () {
        expect(Freshness::Aggregate->value)->toBe('aggregate')
            ->and(Freshness::Realtime->value)->toBe('realtime');
    });

    it('aggregate is the default', function () {
        expect(Freshness::default())->toBe(Freshness::Aggregate);
    });

    it('identifies realtime correctly', function () {
        expect(Freshness::Realtime->isRealtime())->toBeTrue()
            ->and(Freshness::Aggregate->isRealtime())->toBeFalse();
    });

    it('returns max delay in minutes', function () {
        expect(Freshness::Aggregate->maxDelayMinutes())->toBe(5)
            ->and(Freshness::Realtime->maxDelayMinutes())->toBe(0);
    });
});
