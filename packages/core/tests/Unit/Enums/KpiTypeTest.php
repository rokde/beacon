<?php

declare(strict_types=1);

use Beacon\Core\Enums\KpiType;

describe('KpiType', function () {
    it('has all six defined types', function () {
        expect(KpiType::SimpleCounter->value)->toBe('simple_counter')
            ->and(KpiType::DecrementCounter->value)->toBe('decrement_counter')
            ->and(KpiType::Gauge->value)->toBe('gauge')
            ->and(KpiType::Rate->value)->toBe('rate')
            ->and(KpiType::Ratio->value)->toBe('ratio')
            ->and(KpiType::Duration->value)->toBe('duration');
    });

    it('can be created from its string value', function () {
        expect(KpiType::from('simple_counter'))->toBe(KpiType::SimpleCounter)
            ->and(KpiType::from('ratio'))->toBe(KpiType::Ratio);
    });

    it('identifies counter types correctly', function () {
        expect(KpiType::SimpleCounter->isCounter())->toBeTrue()
            ->and(KpiType::DecrementCounter->isCounter())->toBeTrue()
            ->and(KpiType::Gauge->isCounter())->toBeFalse()
            ->and(KpiType::Rate->isCounter())->toBeFalse()
            ->and(KpiType::Ratio->isCounter())->toBeFalse()
            ->and(KpiType::Duration->isCounter())->toBeFalse();
    });

    it('identifies ratio type as percentage', function () {
        expect(KpiType::Ratio->isPercentage())->toBeTrue()
            ->and(KpiType::SimpleCounter->isPercentage())->toBeFalse();
    });

    it('identifies gauge type', function () {
        expect(KpiType::Gauge->isGauge())->toBeTrue()
            ->and(KpiType::SimpleCounter->isGauge())->toBeFalse();
    });
});
