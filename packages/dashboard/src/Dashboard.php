<?php

declare(strict_types=1);

namespace Beacon\Dashboard;

use Beacon\Dashboard\ValueObjects\DashboardDefinition;

/**
 * Fluent entry point for dashboard definitions.
 *
 * Usage:
 *   Dashboard::make('sales')
 *       ->label('Sales Overview')
 *       ->path('/sales')
 *       ->authorize(fn ($user) => $user->hasRole('sales_manager'))
 *       ->tiles([...])
 */
final class Dashboard
{
    public static function make(string $id): DashboardDefinition
    {
        return DashboardDefinition::make($id);
    }
}
