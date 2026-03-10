# Beacon Dashboard — Core Conventions

`beacon/dashboard` rendert aggregierte KPI-Daten als konfigurierbare
Dashboards mit Tiles, Vergleichen und Prognosen.
Diese Konventionen gelten **überall** wenn das Paket installiert ist.

## Pflichtstruktur

```
app/Providers/KpiServiceProvider.php   ← alle Dashboard::make()- und KPI::register()-Aufrufe
bootstrap/providers.php                ← KpiServiceProvider eintragen
public/vendor/beacon/                  ← publizierte Assets (einmalig nach Installation)
```

## Dashboard-Registrierung

Alle `DashboardRegistry::register()`-Aufrufe gehören in `KpiServiceProvider`
(oder einen dedizierten `DashboardServiceProvider` bei größeren Apps).
Niemals in Controllern, Middleware oder Route-Dateien registrieren.

## Guard bei Teilinstallation

Dashboard-Code immer absichern:

```php
if ($this->app->bound(\Beacon\Dashboard\Services\DashboardRegistry::class)) {
// Dashboard-Registrierung hier
}
```

## Pfade sind relativ

`->path('/sales')` ist relativ zum konfigurierten `base_path` (default: `/kpi`).
Die vollständige URL ist `base_path + path`, z.B. `/kpi/sales`.
Den base_path niemals hardcoden — er ist per `.env`-Variable `KPI_DASHBOARD_PATH` konfigurierbar.

## Autorisierung in Produktion

Jedes Dashboard mit nicht-öffentlichen Daten **muss** `->authorize()` haben.
Ohne Callback ist das Dashboard öffentlich zugänglich.

## Tile-Typ passend zu KpiType

| KpiType | Empfohlener Tile-Stil |
|---|---|
| `SimpleCounter`, `DecrementCounter` | MetricTile (default) oder ChartTile `type: bar` |
| `Gauge` | ChartTile `type: line` |
| `Ratio` | GaugeTile (Radial, 0–100 %) |
| `Rate`, `Duration` | ChartTile `type: line` |

## Assets

Einmalig nach Installation ausführen:

```bash
php artisan vendor:publish --tag=beacon-dashboard-assets
```

Die kompilierten Dateien (`public/vendor/beacon/`) in Git committen —
kein Node.js-Build-Schritt in der Host-App nötig.

## Polling-Intervall

Default: 300 Sekunden. Für operative Dashboards mit Echtzeit-KPIs: 30–60 Sekunden.
Niemals unter 10 Sekunden — jeder Poll ist ein vollständiger Server-Side-Render.
