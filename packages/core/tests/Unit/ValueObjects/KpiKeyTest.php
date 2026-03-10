<?php

declare(strict_types=1);

use Beacon\Core\Exceptions\InvalidKpiKeyException;
use Beacon\Core\ValueObjects\KpiKey;

describe('KpiKey', function () {
    it('can be created from a valid string', function () {
        $key = KpiKey::fromString('new_registrations');

        expect((string) $key)->toBe('new_registrations');
    });

    it('accepts keys with letters, numbers, underscores and hyphens', function () {
        expect(fn () => KpiKey::fromString('my-kpi_01'))->not->toThrow(InvalidKpiKeyException::class);
        expect(fn () => KpiKey::fromString('mrr'))->not->toThrow(InvalidKpiKeyException::class);
        expect(fn () => KpiKey::fromString('conversion_rate_v2'))->not->toThrow(InvalidKpiKeyException::class);
    });

    it('rejects empty keys', function () {
        expect(fn () => KpiKey::fromString(''))->toThrow(InvalidKpiKeyException::class);
    });

    it('rejects keys with spaces', function () {
        expect(fn () => KpiKey::fromString('new registrations'))->toThrow(InvalidKpiKeyException::class);
    });

    it('rejects keys with special characters', function () {
        expect(fn () => KpiKey::fromString('kpi.value'))->toThrow(InvalidKpiKeyException::class);
        expect(fn () => KpiKey::fromString('kpi/value'))->toThrow(InvalidKpiKeyException::class);
        expect(fn () => KpiKey::fromString('kpi@value'))->toThrow(InvalidKpiKeyException::class);
    });

    it('rejects keys longer than 64 characters', function () {
        $tooLong = str_repeat('a', 65);
        expect(fn () => KpiKey::fromString($tooLong))->toThrow(InvalidKpiKeyException::class);
    });

    it('accepts keys up to 64 characters', function () {
        $maxLength = str_repeat('a', 64);
        expect(fn () => KpiKey::fromString($maxLength))->not->toThrow(InvalidKpiKeyException::class);
    });

    it('is equal to another KpiKey with the same value', function () {
        $a = KpiKey::fromString('my_kpi');
        $b = KpiKey::fromString('my_kpi');
        $c = KpiKey::fromString('other_kpi');

        expect($a->equals($b))->toBeTrue()
            ->and($a->equals($c))->toBeFalse();
    });
});
