# SharedDefinition — vollständiges Referenzbeispiel

Verwende dieses Muster wenn **beide** Pakete installiert sind:
`beacon/recorder` + `beacon/dashboard` (oder das Meta-Paket `beacon/beacon`).

## Funktionsprinzip

`KpiDefinition` aus `beacon/core` ist das **einzige** Konfigurationsobjekt.
Jedes Paket liest nur die Aspekte, die es kennt:

```
KpiDefinition::make('key')
 ├── Recorder-Aspekte: type, granularities, retention, listenOn, freshness
 └── Dashboard-Aspekte: label, comparison, comparisons, showForecast, chart, period
```

Unbekannte Methoden werden durch `KpiDefinition::__call()` still ignoriert und
geben `$this` zurück — Chaining bleibt erhalten, kein Fatal Error.

## Vollständiges KpiServiceProvider-Beispiel

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderCompleted;
use App\Events\UserCancelled;
use App\Events\UserRegistered;
use Beacon\Core\Enums\Freshness;
use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Dashboard\Dashboard;
use Beacon\Dashboard\Enums\TileSize;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\Tile;
use Beacon\Dashboard\ValueObjects\Comparison;
use Beacon\Recorder\Facades\KPI;
use Illuminate\Support\ServiceProvider;

final class KpiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ── KPI-Definitionen ───────────────────────────────────────────────
        // Recorder liest: type, granularities, retention, listenOn
        // Dashboard liest: label, comparison, showForecast, chart
        // Unbekannte Methoden werden still ignoriert

        KPI::register(
            KpiDefinition::make('new_registrations')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
                ->retention(90)
                ->listenOn(UserRegistered::class, fn ($e) => 1)
                ->label('Neue Registrierungen')
                ->comparison(Comparison::previousPeriod())
                ->showForecast(horizon: '30 days'),
        );

        KPI::register(
            KpiDefinition::make('mrr')
                ->type(KpiType::Gauge)
                ->granularities([Granularity::Day, Granularity::Month])
                ->retention(365)
                ->label('Monthly Recurring Revenue')
                ->comparison(Comparison::offset('-1 month'))
                ->chart('line', 240),
        );

        KPI::register(
            KpiDefinition::make('churn_rate')
                ->type(KpiType::Ratio)
                ->granularities([Granularity::Day, Granularity::Week])
                ->listenOn(UserCancelled::class, fn ($e) => $this->calculateChurn())
                ->label('Churn Rate')
                ->comparison(Comparison::previousPeriod()),
        );

        KPI::register(
            KpiDefinition::make('order_revenue')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
                ->retention(365)
                ->listenOn(OrderCompleted::class, fn ($e) => $e->order->total)
                ->label('Umsatz')
                ->comparison(Comparison::offset('-1 week'))
                ->chart('bar', 200),
        );

        // ── Dashboard-Registrierung ────────────────────────────────────────
        // Guard: läuft nur wenn beacon/dashboard installiert ist
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->register(
            Dashboard::make('growth')
                ->label('Growth Overview')
                ->path('/growth')
                ->authorize(fn ($user) => $user->hasRole('analyst'))
                ->refreshInterval(300)
                ->tiles([
                    Tile::kpi('new_registrations')->size(TileSize::Medium),
                    Tile::kpi('mrr')->size(TileSize::Large),
                    Tile::kpi('churn_rate')->size(TileSize::Small),
                ]),
        );

        $registry->register(
            Dashboard::make('revenue')
                ->label('Revenue')
                ->path('/revenue')
                ->authorize(fn ($user) => $user->hasRole('finance'))
                ->refreshInterval(600)
                ->tiles([
                    Tile::kpi('order_revenue')->size(TileSize::Large),
                    Tile::kpi('mrr')->size(TileSize::Medium),
                ]),
        );
    }
}
```

## Was bei Teilinstallation ignoriert wird

| Methode | Ignoriert wenn |
|---|---|
| `->label()` | Nur `beacon/recorder` installiert |
| `->comparison()` | Nur `beacon/recorder` installiert |
| `->comparisons()` | Nur `beacon/recorder` installiert |
| `->showForecast()` | Nur `beacon/recorder` installiert |
| `->chart()` | Nur `beacon/recorder` installiert |
| `->period()` | Nur `beacon/recorder` installiert |

Das Verhalten ist durch `KpiDefinition::__call()` garantiert — kein Fehler bei Teilinstallation.

## Regeln

1. **Immer `KPI::register($definition)` aufrufen** — der Recorder braucht die Registrierung immer.
2. **Dashboard-Code mit `if (! $this->app->bound(DashboardRegistry::class))` absichern** — sonst schlägt die App fehl wenn nur Recorder installiert ist.
3. **`KPI::register()` immer vor Dashboard-Registrierung** — Tiles verweisen auf KPI-Keys die bereits bekannt sein müssen.
4. **Keine doppelten Key-Strings** — bei SharedDefinition den Key nicht in `KpiDefinition::make()` UND nochmal in `Tile::kpi()` neu schreiben; das Tile referenziert den Key der registrierten Definition.
