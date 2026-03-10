<?php

declare(strict_types=1);

use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Facades\KPI;
use Beacon\Recorder\Services\KpiRegistry;

// Fake event classes for testing
class UserRegisteredEvent
{
    public function __construct(public readonly int $userId = 1)
    {
    }
}

class OrderCompletedEvent
{
    public function __construct(public readonly float $total = 99.99)
    {
    }
}

describe('KpiEventListener via listenOn', function () {
    it('records value=1 when a SimpleCounter event is dispatched', function () {
        KPI::fake();

        app(KpiRegistry::class)->register(
            KpiDefinition::make('signups')
                ->type(KpiType::SimpleCounter)
                ->listenOn(UserRegisteredEvent::class, fn ($e) => 1),
        );

        event(new UserRegisteredEvent(userId: 42));

        KPI::assertRecorded('signups', 1);
    });

    it('extracts value from the event payload', function () {
        KPI::fake();

        app(KpiRegistry::class)->register(
            KpiDefinition::make('order_value')
                ->type(KpiType::Gauge)
                ->listenOn(OrderCompletedEvent::class, fn ($e) => $e->total),
        );

        event(new OrderCompletedEvent(total: 249.50));

        KPI::assertRecorded('order_value', 249.50);
    });

    it('records for each of multiple events that map to the same kpi', function () {
        KPI::fake();

        app(KpiRegistry::class)->register(
            KpiDefinition::make('user_interactions')
                ->type(KpiType::SimpleCounter)
                ->listenOn(UserRegisteredEvent::class, fn ($e) => 1)
                ->listenOn(OrderCompletedEvent::class, fn ($e) => 1),
        );

        event(new UserRegisteredEvent());
        event(new OrderCompletedEvent());

        KPI::assertRecordedTimes('user_interactions', 2);
    });

    it('does not record when no matching event is dispatched', function () {
        KPI::fake();

        app(KpiRegistry::class)->register(
            KpiDefinition::make('signups')
                ->type(KpiType::SimpleCounter)
                ->listenOn(UserRegisteredEvent::class, fn ($e) => 1),
        );

        // No event dispatched
        KPI::assertNotRecorded('signups');
    });
});
