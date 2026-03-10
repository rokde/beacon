<?php

declare(strict_types=1);

namespace Beacon\Core\ValueObjects;

/**
 * Binds a Laravel event class to a closure that extracts
 * the numeric KPI value from an event instance.
 *
 * @phpstan-type Extractor callable(object): (int|float)
 */
final class EventListenerDefinition
{
    /** @var callable(object): (float|int) */
    public readonly mixed $extractor;

    /**
     * @param class-string                  $eventClass Fully qualified event class name
     * @param callable(object): (float|int) $extractor  Extracts the value from the event
     */
    public function __construct(
        public readonly string $eventClass,
        mixed $extractor,
    ) {
        $this->extractor = $extractor;
    }
}
