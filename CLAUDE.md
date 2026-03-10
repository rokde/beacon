# Beacon — Laravel KPI Service Package

Monorepo mit drei Composer-Paketen für KPI-Erfassung, Aggregation und
Dashboard-Visualisierung in Laravel-Applikationen.

## Paketstruktur

```
beacon/
├── packages/
│   ├── core/       beacon/core      — Contracts, Enums, KpiDefinition Value Object
│   ├── recorder/   beacon/recorder  — Event-Erfassung, Aggregation, Queue-Jobs
│   ├── dashboard/  beacon/dashboard — Query Engine, Blade-Views, Tiles, Forecast
│   └── beacon/     beacon/beacon    — Meta-Paket (requires alle drei)
├── composer.json   — Monorepo-Root (path-repositories, gemeinsamer vendor/)
├── phpunit.xml     — Root-PHPUnit (alle Testsuites)
├── phpstan.neon    — Root-PHPStan (alle packages/*/src)
├── rector.php      — Root-Rector
├── pint.json       — Shared Pint-Konfiguration (Pakete erben via extends)
└── Makefile        — Alle Developer-Shortcuts
```

## Namespaces

```
Beacon\Core\        → packages/core/src/
Beacon\Recorder\    → packages/recorder/src/
Beacon\Dashboard\   → packages/dashboard/src/
```

## Wichtige Klassen

### beacon/core
- `KpiDefinition` — immutables Value Object, SharedDefinition-Pattern via `__call()`
- `KpiType` — Enum: SimpleCounter, DecrementCounter, Gauge, Rate, Ratio, Duration
- `Granularity` — Enum: Minute, Hour, Day, Week, Month, Year
- `Freshness` — Enum: Aggregate (max 5 min), Realtime (sofort)
- `KpiRecorderContract` — Interface das recorder implementiert

### beacon/recorder
- `KPI` — Facade auf `KpiRecorderContract` (`KPI::record()`, `KPI::register()`, `KPI::fake()`)
- `KpiRecorderService` — Hauptimplementierung (buffering-Logik für sync-Queue)
- `KpiRegistry` — In-Memory-Store für registrierte KpiDefinitions
- `KpiWriteBuffer` — Puffer für HTTP-Request-Kontext mit sync-Queue
- `KpiRecordingMiddleware` — Terminable Middleware, flusht Buffer nach Response
- `AggregateKpiJob` — Aggregiert kpi_events → kpi_aggregates
- `RecordKpiEventJob` — Schreibt einen Rohwert in kpi_events
- `KpiFake` — Test-Double, aktiviert via `KPI::fake()`
- Artisan: `kpi:install`, `kpi:aggregate`, `kpi:reaggregate {kpi} [--date=] [--sync]`

### beacon/dashboard
- `Dashboard` — Fluent builder für `DashboardDefinition::make()`
- `Tile` — Fluent builder für `TileDefinition::kpi()`
- `DashboardRegistry` — Singleton, speichert alle Dashboard-Definitionen
- `QueryEngine` — Liest kpi_aggregates, berechnet Trends, cachet Ergebnisse
- `ForecastEngine` — Lineare Regression + 90%-Konfidenzintervalle
- `DashboardController` — Rendert Dashboard oder Partial-Grid (Polling)
- `Comparison` — Value Object für Zeitvergleiche (`previousPeriod()`, `offset()`)
- `TileSize` — Enum: Small (1/4), Medium (1/2), Large (Vollbreite)

## Entwicklungsworkflow

```bash
# Setup
composer install          # oder: make install

# Tests (alle Pakete)
make test                 # Coverage ≥80% + Type Coverage 100%
make test-core
make test-recorder
make test-dashboard

# PHP Quality
make analyse              # PHPStan level 10 via Larastan
make format               # Pint auto-fix
make format-check         # Pint dry-run (CI)
make refactor             # Rector apply
make refactor-check       # Rector dry-run (CI)

# Frontend (packages/dashboard)
make frontend-install     # npm ci
make frontend-build       # Vite production build → dist/
make frontend-lint        # oxlint
make frontend-format      # Prettier
make frontend-typecheck   # tsc --noEmit

# Alles auf einmal (wie CI)
make all-ci
```

## Quality-Standards

- **PHPStan**: Level 10, `phpstan/phpstan-strict-rules`, via Larastan (recorder/dashboard)
- **Rector**: PHP 8.4, 8 Sets inkl. TYPE_DECLARATION, DEAD_CODE, STRICT_BOOLEANS
- **Pint**: PSR-12 + `declare_strict_types`, `strict_comparison`, `trailing_comma_in_multiline`
- **Test Coverage**: ≥80% (hard threshold in CI)
- **Type Coverage**: 100% (Pest `--type-coverage --min=100`)
- **TypeScript**: oxlint (strict) + Prettier, `explicit-function-return-type` required
- **Frontend-Build**: Vite 6, Tailwind v4, Chart.js (tree-shaken), committed `dist/`

Jede neue PHP-Datei beginnt mit `declare(strict_types=1);`.
Alle Value Objects sind immutable (fluent setters geben `clone $this` zurück).

## Testing-Konventionen

```php
// Pest — alle Tests
// KPI::fake() in beforeEach() für alles was KPI::record() berührt
KPI::fake();
KPI::assertRecorded('key', $value);
KPI::assertNotRecorded('key');
KPI::assertRecordedTimes('key', 1);

// Orchestra Testbench für Recorder und Dashboard
// SQLite :memory: als kpi-Connection
```

## Neue KPI anlegen — Kurzreferenz

```php
// app/Providers/KpiServiceProvider.php → boot()
KPI::register(
    KpiDefinition::make('snake_case_key')   // max 64 Zeichen
        ->type(KpiType::SimpleCounter)       // SUM/LAST/AVG — sorgfältig wählen
        ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
        ->retention(90)
        ->listenOn(SomeLaravelEvent::class, fn ($e) => $e->value)
        // Dashboard-Aspekte (ignored wenn nur recorder installiert):
        ->label('Anzeigename')
        ->comparison(Comparison::previousPeriod())
        ->showForecast(horizon: '30 days'),
);
```

## Neues Dashboard anlegen — Kurzreferenz

```php
// Im selben KpiServiceProvider, nach KPI::register()-Aufrufen:
if (! $this->app->bound(DashboardRegistry::class)) return;

$this->app->make(DashboardRegistry::class)->register(
    Dashboard::make('id')                      // → Route: /kpi/id
        ->label('Anzeigename')
        ->path('/id')
        ->authorize(fn (?User $user) => $user?->hasRole('role') ?? false)
        ->refreshInterval(300)
        ->tiles([
            Tile::kpi('key')->size(TileSize::Medium)->comparison(Comparison::previousPeriod()),
            Tile::kpi('key2')->size(TileSize::Large)->chart('line', 240)->showForecast(horizon: '30 days'),
        ]),
);
```

## .env-Schlüssel

```dotenv
KPI_DB_CONNECTION=kpi          # DB-Connection für kpi_events + kpi_aggregates
KPI_QUEUE=redis                # Queue-Driver (sync nur lokal)
KPI_QUEUE_NAME=kpi             # Queue-Name für dedizierte Worker
KPI_AGGREGATION_INTERVAL=5    # Aggregations-Intervall in Minuten (1–60)
KPI_RETENTION_DAYS=30          # Retention für kpi_events-Rohdaten
KPI_DASHBOARD_PATH=/kpi        # URL-Prefix für alle Dashboards
KPI_APP_NAME=Beacon            # Browser-Titel
KPI_REFRESH_INTERVAL=300       # Default-Polling-Intervall (Sekunden)
```

## GitHub Actions

- **CI** (`.github/workflows/ci.yml`): läuft auf jedem Push/PR
    - `php-quality`: Matrix [core, recorder, dashboard] × PHP 8.4 — format, rector, phpstan, test, type-coverage
    - `php-compat`: PHP 8.5 smoke-test
    - `frontend`: format, lint, typecheck, build + dist/-Freshness-Check
- **Split** (`.github/workflows/split.yml`): läuft auf Tags `v*`
    - Build assets → commit dist/ → Split zu drei separaten Repos
    - Split-Repos: `rokde/beacon-core`, `rokde/beacon-recorder`, `rokde/beacon-dashboard`

## Laravel Boost

Beide Pakete liefern Boost-Ressourcen für Auto-Discovery:

```
packages/recorder/resources/boost/
├── guidelines/core.blade.php            ← Konventionen, immer geladen
└── skills/beacon-kpi-recording/
    ├── SKILL.md                         ← on-demand: KPI anlegen, testen
    └── references/kpi-types.md
    └── references/shared-definition.md

packages/dashboard/resources/boost/
├── guidelines/core.blade.php            ← Konventionen, immer geladen
└── skills/beacon-dashboard-configuration/
    ├── SKILL.md                         ← on-demand: Dashboard konfigurieren
    └── references/tile-types.md
    └── references/authorization.md
```

Discovery via `extra.boost` in den jeweiligen `composer.json`.

## Offene Punkte / Nächste Schritte

- [ ] Testbench-App (`testbench/`) mit vollständigem Beispiel-KpiServiceProvider
- [ ] Alerting-System: `AlertCondition`, `NotificationDispatcher`, `kpi_alerts`-Tabelle
- [ ] `beacon/beacon` Meta-Paket `packages/beacon/composer.json` finalisieren
- [ ] Dashboard-Routing-Tests (Route-Registration, Named Routes, Gate-Checks)
- [ ] `kpi:install --dashboard`-Variante für Nur-Dashboard-Installation
- [ ] README.md pro Paket für Packagist
