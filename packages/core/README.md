# beacon/core

> Shared contracts, enums, and value objects for the Beacon KPI platform.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## What is this?

`beacon/core` is the foundation package of the [Beacon](https://github.com/rokde/beacon) monorepo.
It contains **no Laravel dependency** — only pure PHP contracts, enums, and immutable value objects that are shared by `beacon/recorder` and `beacon/dashboard`.

You do **not** install this package directly. It is pulled in automatically when you install either `beacon/recorder` or `beacon/dashboard`.

---

## Contents

### Contracts

| Interface | Description |
|-----------|-------------|
| `KpiRecorderContract` | Record and register KPI values |
| `KpiEventRepositoryContract` | Persist raw KPI events |
| `KpiAggregateRepositoryContract` | Persist aggregated KPI data |

### Enums

| Enum | Values |
|------|--------|
| `KpiType` | `SimpleCounter`, `DecrementCounter`, `Gauge`, `Rate`, `Ratio`, `Duration` |
| `Granularity` | `Minute`, `Hour`, `Day`, `Week`, `Month`, `Year` |
| `Freshness` | `Aggregate` (max 5 min cached), `Realtime` (live query) |

### Value Objects

| Class | Description |
|-------|-------------|
| `KpiDefinition` | Immutable, fluent builder for a complete KPI definition |
| `KpiKey` | Validated string key (max 64 chars, `[a-z0-9_-]`) |
| `EventListenerDefinition` | Pairs an event class with an extractor closure |

---

## KpiDefinition API

```php
use Beacon\Core\Enums\Freshness;
use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;

$definition = KpiDefinition::make('orders_count')
    ->type(KpiType::SimpleCounter)
    ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
    ->retention(90)                                    // keep raw events for 90 days
    ->freshness(Freshness::Aggregate)
    ->listenOn(OrderPlaced::class, fn ($e) => $e->quantity);
```

All fluent setters return a **new immutable instance** — the original is never mutated.

Unknown methods (e.g. dashboard-only `.label()`, `.showForecast()`) are silently ignored via `__call`, so a shared definition file works regardless of which Beacon packages are installed.

---

## Requirements

- PHP 8.4+

---

## License

MIT — see [LICENSE](../../LICENSE).
