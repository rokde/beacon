# beacon/recorder

> Laravel package for recording, queuing, and aggregating KPI events into time-series data.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue)](https://www.php.net/)
[![Laravel 11/12](https://img.shields.io/badge/Laravel-11%2F12-red)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Installation

```bash
composer require beacon/recorder
```

Publish config and run migrations:

```bash
php artisan kpi:install
php artisan migrate
```

---

## Configuration

```dotenv
KPI_DB_CONNECTION=kpi          # DB connection for kpi_events + kpi_aggregates
KPI_QUEUE=redis                # Queue driver (use sync only locally)
KPI_QUEUE_NAME=kpi             # Dedicated queue name
KPI_AGGREGATION_INTERVAL=5     # How often to aggregate, in minutes (1–60)
KPI_RETENTION_DAYS=30          # How long to keep raw kpi_events
```

---

## Quick Start

### 1. Register a KPI

```php
// app/Providers/KpiServiceProvider.php

use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Facades\KPI;

public function boot(): void
{
    KPI::register(
        KpiDefinition::make('orders_placed')
            ->type(KpiType::SimpleCounter)
            ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
            ->retention(90)
            ->listenOn(OrderPlaced::class, fn ($event) => $event->quantity),
    );
}
```

### 2. Record a value manually

```php
use Beacon\Recorder\Facades\KPI;

KPI::record('orders_placed', 1);
KPI::record('revenue_eur', $order->total, ['currency' => 'EUR']);
```

### 3. Schedule aggregation

Aggregation runs automatically via the scheduler if you add the console kernel:

```php
// The RecorderServiceProvider auto-schedules kpi:aggregate every N minutes
// based on KPI_AGGREGATION_INTERVAL.
```

---

## Queue Behavior

| Context | Queue driver | Behavior |
|---------|-------------|----------|
| HTTP request | `sync` | Buffered via `KpiWriteBuffer`, flushed after response |
| HTTP request | `redis` / `database` | Job dispatched immediately |
| CLI / Queue worker | any | Job dispatched immediately |

For `sync` queue, register the terminable middleware:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Beacon\Recorder\Middleware\KpiRecordingMiddleware::class);
})
```

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `kpi:install` | Publish config + create migrations |
| `kpi:aggregate` | Aggregate pending raw events into `kpi_aggregates` |
| `kpi:reaggregate {kpi}` | Re-aggregate a specific KPI (after data correction) |

---

## Testing

```php
use Beacon\Recorder\Facades\KPI;

beforeEach(fn () => KPI::fake());

it('records the KPI', function () {
    KPI::record('orders_placed', 3);

    KPI::assertRecorded('orders_placed', 3);
    KPI::assertRecordedTimes('orders_placed', 1);
});
```

---

## Requirements

- PHP 8.4+
- Laravel 11 or 12
- A dedicated database connection for KPI tables (recommended: separate DB)

---

## License

MIT — see [LICENSE](../../LICENSE).
