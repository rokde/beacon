# Beacon Recorder — Core Conventions

`beacon/recorder` erfasst KPI-Events und aggregiert sie in Zeitbuckets.
Diese Konventionen gelten **überall** wenn das Paket installiert ist.

## Pflichtstruktur

```
app/Providers/KpiServiceProvider.php   ← alle KPI::register()-Aufrufe hier
bootstrap/providers.php                ← KpiServiceProvider eintragen
config/kpi-recorder.php                ← nach kpi:install vorhanden
```

`KpiServiceProvider` ist **immer** der einzige Ort für Definitionen.
Niemals in Controllern, Modellen, Jobs oder Route-Dateien registrieren.

## KPI-Keys

- Format: `snake_case`, a-z, 0-9, Unterstriche, max. 64 Zeichen
- Stabile Namen — Änderungen brechen historische Aggregate
- ✓ `new_registrations`, `order_revenue`, `active_sessions`
- ✗ `kpi1`, `Registrations`, `reg`

## Facade vs. Dependency Injection

```php
// Imperativ in Controllern/Listenern/Jobs — Facade verwenden
use Beacon\Recorder\Facades\KPI;
KPI::record('order_revenue', $order->total);

// In Services, die testbar sein sollen — Contract injecten
use Beacon\Core\Contracts\KpiRecorderContract;
public function __construct(private KpiRecorderContract $kpi) {}
```

## KpiType-Wahl (wichtigste Entscheidung)

| Type | Aggregation | Anwendung |
|---|---|---|
| `SimpleCounter` | SUM | Nur-Hochzähler (Käufe, Signups, Seitenaufrufe) |
| `DecrementCounter` | NET-SUM | Hoch+runter (offene Tickets, aktive Sessions) |
| `Gauge` | LAST | Momentaufnahme (MRR, Lagerbestand, User-Anzahl) |
| `Rate` | AVG | Durchsatz pro Zeiteinheit (Requests/min) |
| `Ratio` | AVG | Prozentsatz 0–100 (Conversion Rate, Churn) |
| `Duration` | AVG | Zeitspannen in Sekunden (Response Time) |

## Granularität

`Granularity::Minute` ist **opt-in** und darf nur für Echtzeit-Alerting aktiviert werden.
Default (wenn nichts angegeben): `[Hour, Day, Week, Month, Year]`.

## SharedDefinition-Pflicht bei Dashboard-Installation

Wenn `beacon/dashboard` installiert ist, **immer** `KpiDefinition::make()` aus
`beacon/core` verwenden — niemals separate Recorder-Konfiguration plus separates
Tile. `KpiDefinition` ist dann die einzige Quelle für beide Pakete.

## Testing

```php
// Immer KPI::fake() — niemals echte kpi_events-Tabelle in Tests befüllen
KPI::fake();
// Aktion auslösen ...
KPI::assertRecorded('my_kpi', 1);
KPI::assertNotRecorded('other_kpi');
KPI::assertRecordedTimes('my_kpi', 1);
```

## Queue-Konfiguration

`queue_connection = sync` ist für lokale Entwicklung akzeptabel.
In Produktion immer eine echte Queue (redis, database, sqs).
Mit sync-Queue muss `KpiRecordingMiddleware` global registriert sein:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
$middleware->append(\Beacon\Recorder\Middleware\KpiRecordingMiddleware::class);
})
```
