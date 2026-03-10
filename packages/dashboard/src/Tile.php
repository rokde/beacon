<?php

declare(strict_types=1);

namespace Beacon\Dashboard;

use Beacon\Dashboard\ValueObjects\TileDefinition;

/**
 * Fluent entry point for tile definitions.
 *
 * Usage:
 *   Tile::kpi('new_registrations')
 *       ->label('New Registrations')
 *       ->size(TileSize::Medium)
 *       ->comparison(Comparison::previousPeriod())
 */
final class Tile
{
    public static function kpi(string $kpiKey): TileDefinition
    {
        return TileDefinition::kpi($kpiKey);
    }
}
