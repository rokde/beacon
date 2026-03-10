<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Http\Controllers;

use Beacon\Core\Enums\Freshness;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\Services\QueryEngine;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class DashboardController
{
    public function __construct(
        private readonly DashboardRegistry $registry,
        private readonly QueryEngine $query,
    ) {}

    public function show(Request $request, string $path = ''): SymfonyResponse
    {
        $normalizedPath = '/'.ltrim($path, '/');
        $dashboard = $this->registry->findByPath($normalizedPath);

        if ($dashboard === null) {
            abort(404);
        }

        $user = $request->user();

        if (! $dashboard->isAuthorized($user)) {
            abort(403);
        }

        $tilesData = [];

        foreach ($dashboard->getTiles() as $tile) {
            $freshness = Freshness::Aggregate; // TODO: read from KpiDefinition when beacon/recorder is present
            $tilesData[$tile->kpiKey] = $this->query->queryForTile($tile, $freshness);
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
