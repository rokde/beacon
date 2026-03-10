<?php

declare(strict_types=1);

use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Jobs\AggregateKpiJob;
use Beacon\Recorder\Jobs\RecordKpiEventJob;
use Beacon\Recorder\Services\KpiRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

describe('Record → Aggregate Pipeline (Integration)', function () {
    beforeEach(function () {
        app(KpiRegistry::class)->register(
            KpiDefinition::make('daily_signups')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day, Granularity::Hour])
                ->retention(30),
        );
    });

    it('records an event and aggregates it into kpi_aggregates', function () {
        // Step 1: Run the RecordKpiEventJob (simulates terminate/queue dispatch)
        (new RecordKpiEventJob(
            kpiKey: 'daily_signups',
            value: 1,
            recordedAt: new DateTimeImmutable('2024-06-15 14:30:00'),
            meta: [],
        ))->handle();

        expect(DB::connection('kpi')->table('kpi_events')->count())->toBe(1);

        // Step 2: Aggregate
        (new AggregateKpiJob('daily_signups'))->handle(app(KpiRegistry::class));

        $dayAggregate = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'daily_signups')
            ->where('granularity', 'day')
            ->where('period_start', '2024-06-15 00:00:00')
            ->first();

        expect($dayAggregate)->not->toBeNull()
            ->and((float) $dayAggregate->value)->toBe(1.0)
            ->and((int) $dayAggregate->count)->toBe(1);
    });

    it('accumulates multiple events into one aggregate for the same day', function () {
        $dates = [
            '2024-06-15 08:00:00',
            '2024-06-15 12:30:00',
            '2024-06-15 23:59:00',
        ];

        foreach ($dates as $date) {
            (new RecordKpiEventJob('daily_signups', 1, new DateTimeImmutable($date), []))->handle();
        }

        (new AggregateKpiJob('daily_signups'))->handle(app(KpiRegistry::class));

        $aggregate = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'daily_signups')
            ->where('granularity', 'day')
            ->first();

        expect((float) $aggregate->value)->toBe(3.0)
            ->and((int) $aggregate->count)->toBe(3);
    });

    it('keeps events from different days in separate aggregates', function () {
        (new RecordKpiEventJob('daily_signups', 1, new DateTimeImmutable('2024-06-14 23:00:00'), []))->handle();
        (new RecordKpiEventJob('daily_signups', 1, new DateTimeImmutable('2024-06-15 01:00:00'), []))->handle();

        (new AggregateKpiJob('daily_signups'))->handle(app(KpiRegistry::class));

        $aggregates = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'daily_signups')
            ->where('granularity', 'day')
            ->orderBy('period_start')
            ->get();

        expect($aggregates)->toHaveCount(2)
            ->and($aggregates[0]->period_start)->toStartWith('2024-06-14')
            ->and($aggregates[1]->period_start)->toStartWith('2024-06-15');
    });

    it('respects retention and deletes old raw events after aggregation', function () {
        app(KpiRegistry::class)->register(
            KpiDefinition::make('daily_signups')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day])
                ->retention(7),
        );

        // Insert a very old event (beyond retention)
        DB::connection('kpi')->table('kpi_events')->insert([
            'kpi_key'     => 'daily_signups',
            'value'       => 1,
            'recorded_at' => now()->subDays(10)->toDateTimeString(),
            'meta'        => '{}',
        ]);

        // Insert a recent event (within retention)
        DB::connection('kpi')->table('kpi_events')->insert([
            'kpi_key'     => 'daily_signups',
            'value'       => 1,
            'recorded_at' => now()->subDays(2)->toDateTimeString(),
            'meta'        => '{}',
        ]);

        (new AggregateKpiJob('daily_signups'))->handle(app(KpiRegistry::class));

        // Old event deleted, recent one kept
        expect(DB::connection('kpi')->table('kpi_events')->count())->toBe(1);
    });
});
