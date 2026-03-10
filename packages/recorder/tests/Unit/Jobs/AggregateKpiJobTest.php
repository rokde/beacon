<?php

declare(strict_types=1);

use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Jobs\AggregateKpiJob;
use Beacon\Recorder\Services\KpiRegistry;
use Illuminate\Support\Facades\DB;

describe('AggregateKpiJob', function () {
    beforeEach(function () {
        // Register a simple counter KPI
        $this->registry = app(KpiRegistry::class);
        $this->definition = KpiDefinition::make('signups')
            ->type(KpiType::SimpleCounter)
            ->granularities([Granularity::Day, Granularity::Hour]);
        $this->registry->register($this->definition);
    });

    it('aggregates raw events into kpi_aggregates', function () {
        // Seed raw events for the same day
        $day = '2024-06-15';

        foreach (range(1, 3) as $i) {
            DB::connection('kpi')->table('kpi_events')->insert([
                'kpi_key'     => 'signups',
                'value'       => 1,
                'recorded_at' => "{$day} 10:0{$i}:00",
                'meta'        => '{}',
            ]);
        }

        (new AggregateKpiJob('signups'))->handle($this->registry);

        $aggregate = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'signups')
            ->where('granularity', 'day')
            ->where('period_start', "{$day} 00:00:00")
            ->first();

        expect($aggregate)->not->toBeNull()
            ->and((float) $aggregate->value)->toBe(3.0)
            ->and((int) $aggregate->count)->toBe(3);
    });

    it('aggregates at hour granularity as well', function () {
        DB::connection('kpi')->table('kpi_events')->insert([
            ['kpi_key' => 'signups', 'value' => 1, 'recorded_at' => '2024-06-15 10:05:00', 'meta' => '{}'],
            ['kpi_key' => 'signups', 'value' => 1, 'recorded_at' => '2024-06-15 10:45:00', 'meta' => '{}'],
            ['kpi_key' => 'signups', 'value' => 1, 'recorded_at' => '2024-06-15 11:10:00', 'meta' => '{}'],
        ]);

        (new AggregateKpiJob('signups'))->handle($this->registry);

        $hour10 = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'signups')
            ->where('granularity', 'hour')
            ->where('period_start', '2024-06-15 10:00:00')
            ->first();

        $hour11 = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'signups')
            ->where('granularity', 'hour')
            ->where('period_start', '2024-06-15 11:00:00')
            ->first();

        expect((float) $hour10->value)->toBe(2.0)
            ->and((float) $hour11->value)->toBe(1.0);
    });

    it('is idempotent — running twice yields the same aggregate', function () {
        DB::connection('kpi')->table('kpi_events')->insert([
            'kpi_key' => 'signups', 'value' => 5, 'recorded_at' => '2024-06-15 09:00:00', 'meta' => '{}',
        ]);

        (new AggregateKpiJob('signups'))->handle($this->registry);
        (new AggregateKpiJob('signups'))->handle($this->registry);

        $count = DB::connection('kpi')->table('kpi_aggregates')
            ->where('kpi_key', 'signups')
            ->where('granularity', 'day')
            ->count();

        expect($count)->toBe(1);
    });

    it('deletes raw events after aggregation that exceed retention policy', function () {
        // Override definition with 0-day retention for this test
        $this->registry->register(
            KpiDefinition::make('signups')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day])
                ->retention(0),
        );

        DB::connection('kpi')->table('kpi_events')->insert([
            'kpi_key' => 'signups', 'value' => 1, 'recorded_at' => '2020-01-01 00:00:00', 'meta' => '{}',
        ]);

        (new AggregateKpiJob('signups'))->handle($this->registry);

        expect(DB::connection('kpi')->table('kpi_events')->count())->toBe(0);
    });

    it('does nothing for an unknown kpi key', function () {
        expect(fn () => (new AggregateKpiJob('unknown_kpi'))->handle($this->registry))
            ->not->toThrow(Throwable::class);

        expect(DB::connection('kpi')->table('kpi_aggregates')->count())->toBe(0);
    });

    it('does not aggregate minute granularity unless explicitly configured', function () {
        DB::connection('kpi')->table('kpi_events')->insert([
            'kpi_key' => 'signups', 'value' => 1, 'recorded_at' => '2024-06-15 10:05:00', 'meta' => '{}',
        ]);

        (new AggregateKpiJob('signups'))->handle($this->registry);

        $minuteAggregates = DB::connection('kpi')->table('kpi_aggregates')
            ->where('granularity', 'minute')
            ->count();

        expect($minuteAggregates)->toBe(0);
    });
});
