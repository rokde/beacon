<?php

declare(strict_types=1);

use Beacon\Core\ValueObjects\EventListenerDefinition;

describe('EventListenerDefinition', function () {
    it('holds the event class and extractor closure', function () {
        $extractor = fn ($event) => $event->amount;

        $listener = new EventListenerDefinition(
            eventClass: 'App\Events\OrderCompleted',
            extractor: $extractor,
        );

        expect($listener->eventClass)->toBe('App\Events\OrderCompleted')
            ->and($listener->extractor)->toBe($extractor);
    });

    it('extractor can be called and returns a value', function () {
        $listener = new EventListenerDefinition(
            eventClass: 'App\Events\UserRegistered',
            extractor: fn ($event) => 1,
        );

        $result = ($listener->extractor)(new stdClass());
        expect($result)->toBe(1);
    });
});
