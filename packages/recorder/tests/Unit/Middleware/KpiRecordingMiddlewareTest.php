<?php

declare(strict_types=1);

use Beacon\Recorder\Middleware\KpiRecordingMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('KpiRecordingMiddleware', function () {
    it('passes the request through without modification', function () {
        $middleware = app(KpiRecordingMiddleware::class);
        $request = Request::create('/test', 'GET');
        $response = new Response('OK', 200);

        $result = $middleware->handle($request, fn ($req) => $response);

        expect($result)->toBe($response);
    });

    it('flushes pending kpi writes in terminate()', function () {
        $middleware = app(KpiRecordingMiddleware::class);
        $buffer = app(Beacon\Recorder\Services\KpiWriteBuffer::class);

        $buffer->push('signups', 1, now(), []);
        $buffer->push('signups', 1, now(), []);

        expect($buffer->count())->toBe(2);

        $middleware->terminate(
            Request::create('/test', 'GET'),
            new Response('OK', 200),
        );

        expect($buffer->count())->toBe(0);
    });

    it('dispatches a RecordKpiEventJob for each buffered write on terminate', function () {
        Illuminate\Support\Facades\Queue::fake();

        $middleware = app(KpiRecordingMiddleware::class);
        $buffer = app(Beacon\Recorder\Services\KpiWriteBuffer::class);

        $buffer->push('new_registrations', 1, now(), []);
        $buffer->push('order_value', 99.0, now(), ['plan' => 'pro']);

        $middleware->terminate(
            Request::create('/test', 'GET'),
            new Response('OK', 200),
        );

        Illuminate\Support\Facades\Queue::assertPushed(
            Beacon\Recorder\Jobs\RecordKpiEventJob::class,
            2,
        );
    });
});
