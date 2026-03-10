<?php

declare(strict_types=1);

use Beacon\Recorder\Jobs\RecordKpiEventJob;
use Illuminate\Support\Facades\DB;

describe('RecordKpiEventJob', function () {
    it('inserts a row into kpi_events when handled', function () {
        $job = new RecordKpiEventJob(
            kpiKey: 'new_registrations',
            value: 1,
            recordedAt: now(),
            meta: [],
        );

        $job->handle();

        expect(DB::connection('kpi')->table('kpi_events')->count())->toBe(1);
    });

    it('stores the correct kpi_key and value', function () {
        $job = new RecordKpiEventJob(
            kpiKey: 'order_value',
            value: 149.99,
            recordedAt: now(),
            meta: [],
        );

        $job->handle();

        $row = DB::connection('kpi')->table('kpi_events')->first();
        expect($row->kpi_key)->toBe('order_value')
            ->and((float) $row->value)->toBe(149.99);
    });

    it('stores meta as json', function () {
        $job = new RecordKpiEventJob(
            kpiKey: 'order_value',
            value: 50.0,
            recordedAt: now(),
            meta: ['plan' => 'premium', 'country' => 'DE'],
        );

        $job->handle();

        $row = DB::connection('kpi')->table('kpi_events')->first();
        $meta = json_decode($row->meta, associative: true);

        expect($meta)->toBe(['plan' => 'premium', 'country' => 'DE']);
    });

    it('stores the recorded_at timestamp', function () {
        $recordedAt = new DateTimeImmutable('2024-06-15 10:30:00');

        $job = new RecordKpiEventJob(
            kpiKey: 'signups',
            value: 1,
            recordedAt: $recordedAt,
            meta: [],
        );

        $job->handle();

        $row = DB::connection('kpi')->table('kpi_events')->first();
        expect($row->recorded_at)->toStartWith('2024-06-15 10:30:00');
    });

    it('stores empty meta as an empty json object', function () {
        $job = new RecordKpiEventJob(
            kpiKey: 'signups',
            value: 1,
            recordedAt: now(),
            meta: [],
        );

        $job->handle();

        $row = DB::connection('kpi')->table('kpi_events')->first();
        expect(json_decode($row->meta, associative: true))->toBe([]);
    });

    it('can insert multiple events for the same kpi_key', function () {
        foreach (range(1, 5) as $i) {
            (new RecordKpiEventJob('signups', 1, now(), []))->handle();
        }

        expect(DB::connection('kpi')->table('kpi_events')->count())->toBe(5);
    });
});
