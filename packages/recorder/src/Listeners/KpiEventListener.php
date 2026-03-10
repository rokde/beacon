<?php

declare(strict_types=1);

namespace Beacon\Recorder\Listeners;

use Beacon\Core\Contracts\KpiRecorderContract;

/**
 * Dynamically registered listener for each event class registered
 * via KpiDefinition::listenOn().
 *
 * One instance is created per (eventClass → KPI mapping). The closure
 * from EventListenerDefinition::$extractor is called with the event
 * to extract the numeric value.
 */
final class KpiEventListener
{
    /** @var callable(object): (float|int) */
    private $extractor;

    /**
     * @param  callable(object): (float|int)  $extractor
     */
    public function __construct(
        private readonly KpiRecorderContract $recorder,
        private readonly string $kpiKey,
        callable $extractor,
    ) {
        $this->extractor = $extractor;
    }

    public function handle(object $event): void
    {
        $value = ($this->extractor)($event);
        $this->recorder->record($this->kpiKey, $value);
    }
}
