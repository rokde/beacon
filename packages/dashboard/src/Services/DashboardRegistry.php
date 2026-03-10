<?php

declare(strict_types=1);

namespace Beacon\Dashboard\Services;

use Beacon\Dashboard\ValueObjects\DashboardDefinition;

final class DashboardRegistry
{
    /** @var array<string, DashboardDefinition> keyed by dashboard id */
    private array $dashboards = [];

    public function register(DashboardDefinition $dashboard): void
    {
        $this->dashboards[$dashboard->id] = $dashboard;
    }

    public function get(string $id): ?DashboardDefinition
    {
        return $this->dashboards[$id] ?? null;
    }

    /**
     * Find a dashboard by its path (e.g. "/test" or "/sales").
     */
    public function findByPath(string $path): ?DashboardDefinition
    {
        foreach ($this->dashboards as $dashboard) {
            if ($dashboard->getPath() === $path) {
                return $dashboard;
            }
        }

        return null;
    }

    /** @return list<DashboardDefinition> */
    public function all(): array
    {
        return array_values($this->dashboards);
    }

    public function has(string $id): bool
    {
        return isset($this->dashboards[$id]);
    }
}
