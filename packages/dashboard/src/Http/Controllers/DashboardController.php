<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Http\Controllers;

use Beacon\Core\Enums\Freshness;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\Services\QueryEngine;
use Beacon\Dashboard\ValueObjects\DashboardDefinition;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class DashboardController
{
    public function __construct(
        private DashboardRegistry $dashboardRegistry,
        private QueryEngine $queryEngine,
    ) {}

    public function show(Request $request, string $path = ''): SymfonyResponse
    {
        $normalizedPath = '/'.ltrim($path, '/');
        $dashboard = $this->dashboardRegistry->findByPath($normalizedPath);

        if (! $dashboard instanceof DashboardDefinition) {
            abort(404);
        }

        $user = $request->user();

        if (! $dashboard->isAuthorized($user)) {
            abort(403);
        }

        $tilesData = [];

        foreach ($dashboard->getTiles() as $tileDefinition) {
            $freshness = Freshness::Aggregate; // TODO: read from KpiDefinition when beacon/recorder is present
            $tilesData[$tileDefinition->kpiKey] = $this->queryEngine->queryForTile($tileDefinition, $freshness);
        }

        // Partial refresh (polling) — return only the grid HTML
        if ($request->hasHeader('X-Beacon-Refresh')) {
            return response()->view('beacon-dashboard::partials.grid', [
                'dashboard' => $dashboard,
                'tilesData' => $tilesData,
            ]);
        }

        return response()->view('beacon-dashboard::dashboard.show', [
            'dashboard' => $dashboard,
            'tilesData' => $tilesData,
        ]);
    }
}
