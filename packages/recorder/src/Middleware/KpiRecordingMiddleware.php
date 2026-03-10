<?php

declare(strict_types=1);

namespace Beacon\Recorder\Middleware;

use Beacon\Recorder\Jobs\RecordKpiEventJob;
use Beacon\Recorder\Services\KpiWriteBuffer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Terminable middleware that flushes the KpiWriteBuffer after the HTTP
 * response has been sent to the client.
 *
 * Activated only when queue_connection = "sync". In that case KPI::record()
 * pushes to the buffer; this middleware dispatches the actual jobs once
 * the user is no longer waiting.
 *
 * The middleware must be registered globally in the host app's
 * bootstrap/app.php:
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->append(\Beacon\Recorder\Middleware\KpiRecordingMiddleware::class);
 *   })
 */
final readonly class KpiRecordingMiddleware
{
    public function __construct(
        private KpiWriteBuffer $kpiWriteBuffer,
    ) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $response;
    }

    public function terminate(): void
    {
        if ($this->kpiWriteBuffer->isEmpty()) {
            return;
        }

        foreach ($this->kpiWriteBuffer->flush() as $item) {
            $queueConnection = config('kpi-recorder.queue_connection');
            $queueName = config('kpi-recorder.queue_name');
            RecordKpiEventJob::dispatch(
                $item['kpiKey'],
                $item['value'],
                $item['recordedAt'],
                $item['meta'],
            )->onConnection(is_string($queueConnection) ? $queueConnection : null)
                ->onQueue(is_string($queueName) ? $queueName : null);
        }
    }
}
