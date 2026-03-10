# Beacon — Laravel KPI Platform

> Record, aggregate, and visualize KPIs in Laravel applications.

[![CI](https://github.com/rokde/beacon/actions/workflows/ci.yml/badge.svg)](https://github.com/rokde/beacon/actions/workflows/ci.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue)](https://www.php.net/)
[![Laravel 11/12](https://img.shields.io/badge/Laravel-11%2F12-red)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Packages

Beacon is split into three focused packages — install only what you need.

| Package | Description | Repo |
|---------|-------------|------|
| [`beacon/core`](packages/core/README.md) | Contracts, enums, value objects (no Laravel dependency) | [rokde/beacon-core](https://github.com/rokde/beacon-core) |
| [`beacon/recorder`](packages/recorder/README.md) | Event recording, queue jobs, aggregation | [rokde/beacon-recorder](https://github.com/rokde/beacon-recorder) |
| [`beacon/dashboard`](packages/dashboard/README.md) | Blade dashboard UI, query engine, forecasting | [rokde/beacon-dashboard](https://github.com/rokde/beacon-dashboard) |

---

## Installation

**Full stack** (recording + dashboard):

```bash
composer require beacon/recorder beacon/dashboard
php artisan kpi:install
php artisan migrate
php artisan vendor:publish --tag=beacon-dashboard-assets
```

**Recording only** (no UI):

```bash
composer require beacon/recorder
php artisan kpi:install
php artisan migrate
```

---

## Quick Example

```php
// app/Providers/KpiServiceProvider.php

use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Facades\KPI;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\ValueObjects\Dashboard;
use Beacon\Dashboard\ValueObjects\Tile;
use Beacon\Dashboard\ValueObjects\Comparison;

public function boot(): void
{
    // 1. Register a KPI
    KPI::register(
        KpiDefinition::make('orders_placed')
            ->type(KpiType::SimpleCounter)
            ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
            ->retention(90)
            ->listenOn(OrderPlaced::class, fn ($e) => $e->quantity),
    );

    // 2. Record manually anywhere in your app
    KPI::record('orders_placed', 1);

    // 3. Build a dashboard (requires beacon/dashboard)
    if ($this->app->bound(DashboardRegistry::class)) {
        $this->app->make(DashboardRegistry::class)->register(
            Dashboard::make('overview')
                ->label('Business Overview')
                ->path('/overview')
                ->tiles([
                    Tile::kpi('orders_placed')
                        ->label('Orders (30d)')
                        ->comparison(Comparison::previousPeriod()),
                ]),
        );
    }
}
```

The dashboard is now available at `/kpi/overview`.

---

## Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `KPI_DB_CONNECTION` | `kpi` | Database connection for KPI tables |
| `KPI_QUEUE` | `redis` | Queue driver (`sync` for local dev) |
| `KPI_QUEUE_NAME` | `kpi` | Dedicated queue name |
| `KPI_AGGREGATION_INTERVAL` | `5` | Aggregation frequency in minutes |
| `KPI_RETENTION_DAYS` | `30` | How long to keep raw events |
| `KPI_DASHBOARD_PATH` | `/kpi` | URL prefix for dashboards |
| `KPI_APP_NAME` | `Beacon` | Browser tab title |
| `KPI_REFRESH_INTERVAL` | `300` | Dashboard auto-refresh in seconds |

---

## Testing

```php
use Beacon\Recorder\Facades\KPI;

beforeEach(fn () => KPI::fake());

it('tracks the order', function () {
    // trigger your code that calls KPI::record()
    KPI::assertRecorded('orders_placed', 1);
    KPI::assertRecordedTimes('orders_placed', 1);
});
```

---

## Development (Monorepo)

This repository is the development monorepo. The three packages are published as separate Composer packages via an automated split on release tags.

```bash
# Setup
make install

# Run all tests
make test

# PHP quality checks
make analyse        # PHPStan level 10
make format         # Pint auto-fix
make refactor       # Rector apply

# Frontend (dashboard assets)
make frontend-build

# Everything at once (mirrors CI)
make all-ci
```

Run `make help` for the full list of available targets.

### Quality Standards

- PHPStan level 10 with `phpstan-strict-rules` via Larastan
- Rector: PHP 8.4 + strict booleans, dead code, type declarations
- Pint: PSR-12 + `declare_strict_types`
- Test coverage: ≥ 80%
- Type coverage: 100%
- TypeScript: oxlint (strict) + Prettier + `explicit-function-return-type`

---

## Architecture

```
beacon/
├── packages/
│   ├── core/        Pure PHP — no Laravel dependency
│   ├── recorder/    Laravel — event ingestion + aggregation
│   └── dashboard/   Laravel + Blade + Vite/Tailwind
├── tests/           Pest.php — root test bootstrap
├── phpstan.neon     Shared static analysis config
├── rector.php       Shared refactoring config
├── pint.json        Shared formatting config
└── Makefile         Developer shortcuts
```

---

## License

MIT
