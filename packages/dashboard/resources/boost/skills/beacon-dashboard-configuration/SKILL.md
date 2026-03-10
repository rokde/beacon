---
name: beacon-dashboard-configuration
description: >
  Beacon-Dashboards konfigurieren und registrieren.
  Skill aktivieren bei: neues Dashboard anlegen, Tiles hinzufügen oder ändern,
  Zeitvergleiche konfigurieren, Prognose aktivieren, Autorisierung einrichten,
  Dashboard mit KPI-Definitionen aus beacon/recorder verbinden.
---

# Beacon Dashboard Configuration

## Schritt-für-Schritt-Workflow

### Schritt 1 — KPI-Definitionen prüfen

Wenn `beacon/recorder` installiert ist: `KpiServiceProvider` nach bestehenden
`KpiDefinition`-Registrierungen durchsuchen. Die dort verwendeten Keys
identisch in `Tile::kpi('key')` verwenden — keine doppelten Definitionen anlegen.

Sind noch keine KPIs definiert: zuerst Skill `beacon-kpi-recording` anwenden.

### Schritt 2 — Dashboard in KpiServiceProvider registrieren

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Beacon\Core\Enums\Granularity;
use Beacon\Dashboard\Dashboard;
use Beacon\Dashboard\Enums\TileSize;
use Beacon\Dashboard\Services\DashboardRegistry;
use Beacon\Dashboard\Tile;
use Beacon\Dashboard\ValueObjects\Comparison;
use Illuminate\Support\ServiceProvider;

final class KpiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ... KPI::register()-Aufrufe zuerst ...

        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $this->app->make(DashboardRegistry::class)->register(
            Dashboard::make('sales')
                ->label('Sales Overview')
                ->path('/sales')                                  // → /kpi/sales
                ->authorize(fn ($user) => $user->hasRole('sales_manager'))
                ->refreshInterval(300)
                ->tiles([
                    Tile::kpi('new_registrations')
                        ->label('Neue Registrierungen')
                        ->size(TileSize::Medium)
                        ->comparison(Comparison::previousPeriod())
                        ->showForecast(horizon: '30 days'),

                    Tile::kpi('mrr')
                        ->label('MRR')
                        ->size(TileSize::Large)
                        ->chart('line', 240)
                        ->comparison(Comparison::offset('-1 month')),

                    Tile::kpi('conversion_rate')
                        ->label('Conversion Rate')
                        ->size(TileSize::Small),
                ]),
        );
    }
}
```

### Schritt 3 — Assets publizieren (einmalig)

```bash
php artisan vendor:publish --tag=beacon-dashboard-assets
```

Assets landen in `public/vendor/beacon/`. Committen — kein Node-Build nötig.

---

## Dashboard Builder API

```php
Dashboard::make(string $id)
// $id: einzigartiger Bezeichner — wird für Route-Name und Gate-Name verwendet

    ->label(string $label)
// Anzeigename im Dashboard-Header

    ->path(string $path)
// Relativ zum base_path, z.B. '/sales' → vollständige URL: /kpi/sales

    ->authorize(Closure $callback)
// fn(?User $user): bool — weglassen = öffentlich zugänglich

    ->refreshInterval(int $seconds)
// Polling-Intervall in Sekunden, default: 300

    ->tiles(array $tiles)
// list<TileDefinition> — alle Tiles des Dashboards
```

Ergibt folgende Route: `GET /kpi/{path}` → Name: `beacon.dashboard.{id}`
Ergibt folgendes Gate: `beacon.view.{id}`

---

## Tile Builder API

```php
Tile::kpi(string $kpiKey)
// KPI-Key muss mit einer registrierten KpiDefinition übereinstimmen

    ->label(string $label)
// Tile-Überschrift

    ->size(TileSize $size)
// TileSize::Small | TileSize::Medium | TileSize::Large

    ->granularity(Granularity $granularity)
// Granularität für dieses Tile überschreiben (muss in KpiDefinition konfiguriert sein)

    ->period(int $days)
// Lookback-Fenster in Tagen, default: 30

    ->comparison(Comparison $comparison)
// Einzelner Zeitvergleich

    ->comparisons(array $comparisons)
// Mehrere Zeitvergleiche als list<Comparison>

    ->showForecast(string $horizon = '30 days')
// Prognose aktivieren — z.B. '30 days', '7 days', '12 weeks', '3 months'

    ->chart(string $type = 'line', int $height = 200)
// Chart aktivieren: $type = 'line' oder 'bar', $height in Pixel
```

---

## Tile-Größen

| Größe | Rasterbreite | Inhalt |
|---|---|---|
| `TileSize::Small` | 1/4 | Einzelne Zahl, kein Chart |
| `TileSize::Medium` | 1/2 | Zahl + Sparkline + ein Vergleich |
| `TileSize::Large` | Vollbreite | Chart + Prognose + alle Vergleiche |

Vollständige Entscheidungshilfe: `references/tile-types.md`

---

## Zeitvergleiche

```php
use Beacon\Dashboard\ValueObjects\Comparison;

// Gleiches Zeitfenster, eine Periode zurück
Comparison::previousPeriod()

// Fester Zeitversatz
Comparison::offset('-1 week')
Comparison::offset('-1 month')
Comparison::offset('-1 year')

// Festes Fenster an einem Versatz (Start + Länge)
Comparison::offset('-3 days', '3 days')   // 3 Tage, endend vor 3 Tagen
```

Mehrere Vergleiche (im Tooltip und in der Legende angezeigt):

```php
->comparisons([
    Comparison::previousPeriod(),
    Comparison::offset('-1 year'),
])
```

---

## Prognose

Prognose nutzt lineare Regression über historische Aggregate.
Wird als gestrichelte Linie mit schraffiertem Konfidenzbereich dargestellt.

```php
->showForecast(horizon: '30 days')
->showForecast(horizon: '7 days')
->showForecast(horizon: '12 weeks')
->showForecast(horizon: '3 months')
```

Gültige Horizon-Einheiten: `days`, `weeks`, `months` (Zahlenformat: `N unit`).
Prognose nur aktivieren wenn ≥14 historische Datenpunkte vorhanden sind.
Nicht sinnvoll für `Ratio`- oder `Duration`-KPIs außer der Trend ist aussagekräftig.

---

## Autorisierung

```php
// Rollenbasiert (Spatie Permission, Bouncer, etc.)
->authorize(fn ($user) => $user->hasRole('analyst'))

// Mehrere Rollen
->authorize(fn ($user) => $user->hasAnyRole(['admin', 'sales_manager']))

// Policy-basiert
->authorize(fn ($user) => $user->can('view', KpiDashboard::class))

// Null absichern (unauthentifizierte Nutzer)
->authorize(fn (?User $user) => $user !== null && $user->hasRole('analyst'))
```

Gate-Name im Format `beacon.view.{dashboard-id}` — verwendbar in Blade und Policies:

```php
@can('beacon.view.sales')
    <a href="{{ route('beacon.dashboard.sales') }}">Sales KPIs</a>
@endcan
```

Vollständige Autorisierungs-Referenz: `references/authorization.md`

---

## Mehrere Dashboards

```php
$registry = $this->app->make(DashboardRegistry::class);

$registry->register(
    Dashboard::make('sales')
        ->label('Sales')
        ->path('/sales')
        ->authorize(fn ($u) => $u->hasRole('sales'))
        ->tiles([
            Tile::kpi('new_registrations')->size(TileSize::Medium),
            Tile::kpi('order_revenue')->size(TileSize::Large)->chart('bar', 200),
        ]),
);

$registry->register(
    Dashboard::make('ops')
        ->label('Operations')
        ->path('/ops')
        ->authorize(fn ($u) => $u->hasRole('engineer'))
        ->refreshInterval(60)
        ->tiles([
            Tile::kpi('api_response_time')->size(TileSize::Medium)->chart('line', 200),
            Tile::kpi('error_rate')->size(TileSize::Small),
        ]),
);
```

---

## Checkliste — neues Dashboard

- [ ] `if (! $this->app->bound(DashboardRegistry::class))` Guard vorhanden
- [ ] Jeder `Tile::kpi('key')` verweist auf einen per `KPI::register()` registrierten Key
- [ ] `->authorize()` für alle nicht-öffentlichen Dashboards gesetzt
- [ ] Null-Check in `->authorize()` wenn unauthentifizierte Nutzer möglich sind
- [ ] `php artisan vendor:publish --tag=beacon-dashboard-assets` einmalig ausgeführt
- [ ] `public/vendor/beacon/` in Git committed
- [ ] `TileSize::Large` + `->chart()` bei KPIs wo der Verlauf wichtig ist
- [ ] `->showForecast()` nur wenn ≥14 historische Datenpunkte vorhanden
- [ ] Dashboard-URL in Projektdokumentation / Navigation eingetragen
