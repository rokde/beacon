<?php

declare(strict_types=1);

use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Services\KpiRegistry;

describe('KpiRegistry', function () {
    beforeEach(function () {
        $this->registry = new KpiRegistry;
    });

    it('starts empty', function () {
        expect($this->registry->all())->toBeEmpty();
    });

    it('can register a definition', function () {
        $definition = KpiDefinition::make('test_kpi')
            ->type(KpiType::SimpleCounter);

        $this->registry->register($definition);

        expect($this->registry->all())->toHaveCount(1);
    });

    it('retrieves a definition by key string', function () {
        $definition = KpiDefinition::make('new_registrations')
            ->type(KpiType::SimpleCounter);

        $this->registry->register($definition);

        $found = $this->registry->get('new_registrations');
        expect($found)->not->toBeNull()
            ->and((string) $found->key())->toBe('new_registrations');
    });

    it('returns null for an unknown key', function () {
        expect($this->registry->get('unknown_kpi'))->toBeNull();
    });

    it('can register multiple definitions', function () {
        $this->registry->register(KpiDefinition::make('kpi_a')->type(KpiType::SimpleCounter));
        $this->registry->register(KpiDefinition::make('kpi_b')->type(KpiType::Gauge));
        $this->registry->register(KpiDefinition::make('kpi_c')->type(KpiType::Ratio));

        expect($this->registry->all())->toHaveCount(3);
    });

    it('overwrites an existing definition when registered again with the same key', function () {
        $this->registry->register(KpiDefinition::make('mrr')->type(KpiType::Gauge));
        $this->registry->register(KpiDefinition::make('mrr')->type(KpiType::SimpleCounter));

        expect($this->registry->all())->toHaveCount(1)
            ->and($this->registry->get('mrr')->getType())->toBe(KpiType::SimpleCounter);
    });

    it('only returns definitions that have recorder config', function () {
        // A definition without type — dashboard-only, no recorder config
        $this->registry->register(KpiDefinition::make('display_only'));
        $this->registry->register(KpiDefinition::make('tracked')->type(KpiType::SimpleCounter));

        expect($this->registry->withRecorderConfig())->toHaveCount(1)
            ->and((string) $this->registry->withRecorderConfig()[0]->key())->toBe('tracked');
    });
});
