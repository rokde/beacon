<?php

declare(strict_types=1);

use Beacon\Dashboard\Dashboard;
use Beacon\Dashboard\Services\DashboardRegistry;

describe('DashboardRegistry', function () {
    beforeEach(function () {
        $this->registry = new DashboardRegistry();
    });

    it('starts empty', function () {
        expect($this->registry->all())->toBeEmpty();
    });

    it('can register and retrieve a dashboard by id', function () {
        $dashboard = Dashboard::make('sales')->label('Sales');
        $this->registry->register($dashboard);

        expect($this->registry->get('sales'))->not->toBeNull()
            ->and($this->registry->get('sales')->getLabel())->toBe('Sales');
    });

    it('returns null for unknown id', function () {
        expect($this->registry->get('unknown'))->toBeNull();
    });

    it('returns all registered dashboards', function () {
        $this->registry->register(Dashboard::make('sales'));
        $this->registry->register(Dashboard::make('ops'));

        expect($this->registry->all())->toHaveCount(2);
    });

    it('confirms existence with has()', function () {
        $this->registry->register(Dashboard::make('sales'));

        expect($this->registry->has('sales'))->toBeTrue()
            ->and($this->registry->has('unknown'))->toBeFalse();
    });
});
