<?php

declare(strict_types=1);

use Beacon\Dashboard\Dashboard;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\Tile;
use Illuminate\Support\Facades\DB;

describe('DashboardController', function () {
    beforeEach(function () {
        // Register a test dashboard
        app(DashboardRegistry::class)->register(
            Dashboard::make('test')
                ->label('Test Dashboard')
                ->path('/test')
                ->tiles([
                    Tile::kpi('signups')->label('Sign Ups'),
                ]),
        );

        // Seed some aggregate data
        DB::connection('kpi')->table('kpi_aggregates')->insert([
            'kpi_key'      => 'signups',
            'granularity'  => 'day',
            'period_start' => now()->subDays(5)->format('Y-m-d 00:00:00'),
            'value'        => 42,
            'count'        => 42,
            'meta'         => '{}',
            'created_at'   => now()->toDateTimeString(),
            'updated_at'   => now()->toDateTimeString(),
        ]);
    });

    it('renders the dashboard page successfully', function () {
        $response = $this->get('/kpi/test');

        $response->assertStatus(200);
    });

    it('contains the dashboard label', function () {
        $response = $this->get('/kpi/test');
        $response->assertSee('Test Dashboard');
    });

    it('returns 404 for unknown dashboard', function () {
        $this->get('/kpi/unknown')->assertStatus(404);
    });

    it('returns 403 when auth callback denies access', function () {
        app(DashboardRegistry::class)->register(
            Dashboard::make('secret')
                ->path('/secret')
                ->authorize(fn ($user) => false)
                ->tiles([]),
        );

        $this->get('/kpi/secret')->assertStatus(403);
    });

    it('returns partial HTML for polling refresh requests', function () {
        $response = $this->withHeader('X-Beacon-Refresh', '1')->get('/kpi/test');
        $response->assertStatus(200);
        // Partial response should not contain the full layout
        $response->assertDontSee('<html');
    });

    it('shows the tile label in the rendered HTML', function () {
        $response = $this->get('/kpi/test');
        $response->assertSee('Sign Ups');
    });
});
