# beacon/dashboard

> Laravel package for visualizing KPI data as interactive dashboards with real-time polling, trend indicators, and forecasting.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue)](https://www.php.net/)
[![Laravel 11/12](https://img.shields.io/badge/Laravel-11%2F12-red)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Installation

```bash
composer require beacon/dashboard
```

Publish config and assets:

```bash
php artisan vendor:publish --tag=beacon-dashboard-config
php artisan vendor:publish --tag=beacon-dashboard-assets
```

---

## Configuration

```dotenv
KPI_DASHBOARD_PATH=/kpi        # URL prefix for all dashboards
KPI_APP_NAME=Beacon            # Browser tab title
KPI_REFRESH_INTERVAL=300       # Default auto-refresh interval in seconds
```

---

## Quick Start

Register a dashboard in your `KpiServiceProvider`:

```php
use Beacon\Dashboard\Facades\Dashboard;
use Beacon\Dashboard\ValueObjects\Tile;
use Beacon\Dashboard\Enums\TileSize;
use Beacon\Dashboard\ValueObjects\Comparison;

public function boot(): void
{
    // ... KPI::register() calls first ...

    if (! $this->app->bound(\Beacon\Dashboard\Services\DashboardRegistry::class)) {
        return;
    }

    $this->app->make(\Beacon\Dashboard\Services\DashboardRegistry::class)->register(
        Dashboard::make('overview')
            ->label('Business Overview')
            ->path('/overview')
            ->authorize(fn (?User $user) => $user?->isAdmin() ?? false)
            ->refreshInterval(60)
            ->tiles([
                Tile::kpi('orders_placed')
                    ->label('Orders (30d)')
                    ->size(TileSize::Medium)
                    ->comparison(Comparison::previousPeriod()),

                Tile::kpi('revenue_eur')
                    ->label('Revenue')
                    ->size(TileSize::Large)
                    ->chart('line', 240)
                    ->showForecast(horizon: '30 days'),
            ]),
    );
}
```

The dashboard is now available at `/kpi/overview`.

---

## Tiles

### Sizes

| Size | Width |
|------|-------|
| `TileSize::Small` | 1/4 of the grid |
| `TileSize::Medium` | 1/2 of the grid |
| `TileSize::Large` | Full width |

### Comparisons

```php
Comparison::previousPeriod()        // same-length window shifted back
Comparison::offset('-7 days')       // shift window by 7 days
Comparison::offset('-1 month', '7 days') // shift by 1 month, fixed 7-day window
```

### Forecasting

```php
Tile::kpi('revenue_eur')
    ->showForecast(horizon: '30 days')  // linear regression + 90% confidence interval
```

---

## Authorization

Each dashboard has an `authorize()` callback. The package automatically registers a `beacon.view.{id}` gate for each dashboard.

```php
->authorize(fn (?User $user) => $user?->hasRole('analyst') ?? false)
```

Unauthenticated users (`null`) are supported — return `true` for public dashboards.

---

## Freshness / Caching

| Mode | Behavior |
|------|----------|
| `Freshness::Aggregate` | Cached for N minutes (set by `KPI_AGGREGATION_INTERVAL`) |
| `Freshness::Realtime` | No cache, queries raw events |

---

## Requirements

- PHP 8.4+
- Laravel 11 or 12
- `beacon/recorder` for the database schema (migrations are shared)

---

## License

MIT — see [LICENSE](../../LICENSE).
