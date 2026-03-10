<?php

declare(strict_types=1);

use Beacon\Recorder\Facades\KPI;
use Beacon\Recorder\Jobs\RecordKpiEventJob;
use Beacon\Recorder\Services\KpiWriteBuffer;
use Illuminate\Support\Facades\Queue;

describe('KpiRecorder — record() routing', function () {
    describe('in HTTP context with sync queue', function () {
        beforeEach(function () {
            config(['kpi-recorder.queue_connection' => 'sync']);
        });

        it('adds to the write buffer instead of dispatching directly', function () {
            Queue::fake();
            $buffer = app(KpiWriteBuffer::class);

            // Simulate being inside an HTTP request
            $this->simulateHttpContext();

            KPI::record('signups', 1);

            expect($buffer->count())->toBe(1);
            Queue::assertNothingPushed();
        });
    });

    describe('in HTTP context with async queue (redis/database)', function () {
        beforeEach(function () {
            config(['kpi-recorder.queue_connection' => 'redis']);
        });

        it('dispatches a RecordKpiEventJob immediately', function () {
            Queue::fake();

            $this->simulateHttpContext();

            KPI::record('signups', 1);

            Queue::assertPushed(RecordKpiEventJob::class, 1);
            expect(app(KpiWriteBuffer::class)->count())->toBe(0);
        });
    });

    describe('outside HTTP context (Artisan / Queue workers)', function () {
        it('always dispatches a RecordKpiEventJob regardless of queue driver', function () {
            Queue::fake();
            config(['kpi-recorder.queue_connection' => 'sync']);

            // No HTTP context simulation — running as CLI
            KPI::record('signups', 1);

            Queue::assertPushed(RecordKpiEventJob::class, 1);
        });
    });

    describe('record() with meta', function () {
        it('passes meta data through to the job', function () {
            Queue::fake();
            config(['kpi-recorder.queue_connection' => 'redis']);

            KPI::record('order_value', 149.99, ['plan' => 'premium']);

            Queue::assertPushed(
                RecordKpiEventJob::class,
                fn ($job) => $job->meta === ['plan' => 'premium']
                    && $job->value === 149.99
                    && $job->kpiKey === 'order_value',
            );
        });
    });
});
