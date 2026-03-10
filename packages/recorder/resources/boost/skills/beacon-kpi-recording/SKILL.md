---
name: beacon-kpi-recording
description: >
  KPI mit Beacon Recorder erfassen und registrieren.
  Skill aktivieren bei: neue KPI anlegen, Werte imperativ aufzeichnen,
  Laravel-Events als KPI-Quelle nutzen, KPI-Tests schreiben,
  SharedDefinition einrichten wenn beacon/dashboard ebenfalls installiert ist.
---

# Beacon KPI Recording

## Schritt-für-Schritt-Workflow

### Schritt 1 — Installationsstand prüfen

```bash
composer show | grep beacon
```

| Installiert | Zu verwendendes Muster |
|---|---|
| Nur `beacon/recorder` | Loose Coupling — nur Recorder-Aspekte in `KpiDefinition` |
| `beacon/recorder` + `beacon/dashboard` (oder `beacon/beacon`) | **SharedDefinition** — ein Objekt für beide Pakete |

### Schritt 2 — KpiType wählen

Vollständige Entscheidungshilfe: `references/kpi-types.md`

Kurzreferenz:
- Zählt nur hoch → `KpiType::SimpleCounter`
- Kann hoch und runter → `KpiType::DecrementCounter`
- Absoluter Momentwert (MRR, Bestand) → `KpiType::Gauge`
- Prozentsatz 0–100 → `KpiType::Ratio`
- Zeitspanne in Sekunden → `KpiType::Duration`

### Schritt 3a — Nur Recorder installiert

`app/Providers/KpiServiceProvider.php` anlegen / ergänzen:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\UserRegistered;
use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Recorder\Facades\KPI;
use Illuminate\Support\ServiceProvider;

final class KpiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        KPI::register(
            KpiDefinition::make('new_registrations')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
                ->retention(90)
                ->listenOn(UserRegistered::class, fn ($event) => 1),
        );

        KPI::register(
            KpiDefinition::make('order_revenue')
                ->type(KpiType::SimpleCounter)
                ->granularities([Granularity::Day, Granularity::Month])
                ->retention(365)
                ->listenOn(\App\Events\OrderCompleted::class, fn ($e) => $e->order->total),
        );
    }
}
```

In `bootstrap/providers.php` eintragen:

```php
return [
    // ...
    App\Providers\KpiServiceProvider::class,
];
```

### Schritt 3b — SharedDefinition (Recorder + Dashboard installiert)

Vollständiges Beispiel: `references/shared-definition.md`

Ein `KpiDefinition`-Objekt wird von beiden Paketen konsumiert.
Unbekannte Methoden (z.B. `->label()` wenn nur Recorder installiert) werden
durch `__call()` still ignoriert — kein Fatal Error.

```php
use Beacon\Core\Enums\Granularity;
use Beacon\Core\Enums\KpiType;
use Beacon\Core\ValueObjects\KpiDefinition;
use Beacon\Dashboard\ValueObjects\Comparison;
use Beacon\Recorder\Facades\KPI;

// Recorder liest: type, granularities, retention, listenOn
// Dashboard liest: label, comparison, showForecast
// Unbekannte Seiten werden still ignoriert
KPI::register(
    KpiDefinition::make('new_registrations')
        ->type(KpiType::SimpleCounter)
        ->granularities([Granularity::Day, Granularity::Week, Granularity::Month])
        ->retention(90)
        ->listenOn(UserRegistered::class, fn ($event) => 1)
        ->label('Neue Registrierungen')
        ->comparison(Comparison::previousPeriod())
        ->showForecast(horizon: '30 days'),
);
```

## Werte imperativ aufzeichnen

```php
use Beacon\Recorder\Facades\KPI;

// Einfacher Zähler
KPI::record('page_views', 1);

// Betrag mit Metadaten-Dimension
KPI::record('order_revenue', $order->total, ['plan' => $user->plan]);

// Gauge-Snapshot (absoluter Wert)
KPI::record('active_users', User::active()->count());

// Duration in Sekunden
KPI::record('api_response_time', $durationMs / 1000);
```

`KPI::record()` kann in Controllern, Observers, Jobs und Listenern aufgerufen werden.
**Niemals innerhalb einer Datenbank-Transaktion** — der Event-Write erfolgt asynchron.

## Deklarative Event-Listener

Bevorzugter Weg für Domain-Events — keine manuelle `KPI::record()`-Aufrufe nötig:

```php
KpiDefinition::make('signups')
    ->type(KpiType::SimpleCounter)
    ->listenOn(UserRegistered::class, fn ($event) => 1)
    ->listenOn(UserInvitedAndJoined::class, fn ($event) => 1);
// Mehrere listenOn()-Aufrufe sind additiv
```

Die Extractor-Closure erhält die Event-Instanz und muss `int|float` zurückgeben.

## Externe Datenquellen (Adapter-Pattern)

Für Stripe-Webhooks, externe APIs oder Cron-Imports — `KPI::record()` direkt aufrufen:

```php
// In einem Webhook-Controller
public function handleInvoicePaid(Request $request): Response
{
    $amount = $request->input('data.object.amount_paid') / 100;
    KPI::record('stripe_mrr', $amount);

    return response('', 200);
}

// In einem geplanten Job
public function handle(): void
{
    $mrr = $this->stripeClient->getMrr();
    KPI::record('stripe_mrr', $mrr);
}
```

## Artisan-Befehle

```bash
# Config + Migrations publizieren
php artisan kpi:install

# Migrations ausführen
php artisan migrate

# Aggregation aller KPIs manuell triggern (normalerweise vom Scheduler)
php artisan kpi:aggregate

# Einzelne KPI neu aggregieren (nach Queue-Ausfall oder Datenkorrektur)
php artisan kpi:reaggregate new_registrations
php artisan kpi:reaggregate new_registrations --date=2024-06-01
php artisan kpi:reaggregate new_registrations --date=2024-06-01 --sync
```

## Tests

```php
use Beacon\Recorder\Facades\KPI;

beforeEach(fn () => KPI::fake());

it('zeichnet eine Registrierung auf', function () {
    $this->post('/register', [
        'name'     => 'Jane',
        'email'    => 'jane@example.com',
        'password' => 'secret123',
    ]);

    KPI::assertRecorded('new_registrations', 1);
});

it('zeichnet nichts bei fehlgeschlagener Validierung auf', function () {
    $this->post('/register', []);

    KPI::assertNotRecorded('new_registrations');
});

it('zeichnet pro Bestellung genau einmal auf', function () {
    event(new \App\Events\OrderCompleted($order));

    KPI::assertRecordedTimes('order_revenue', 1);
});

it('zeichnet den korrekten Bestellwert auf', function () {
    event(new \App\Events\OrderCompleted($order));

    KPI::assertRecorded('order_revenue', $order->total);
});
```

## Checkliste — neue KPI hinzufügen

- [ ] `KpiDefinition::make()` in `KpiServiceProvider::boot()` registriert
- [ ] `KpiServiceProvider` in `bootstrap/providers.php` eingetragen
- [ ] `KpiType` entspricht der gewünschten Aggregationslogik (SUM vs. LAST vs. AVG)
- [ ] `Granularity::Minute` nur gesetzt wenn Echtzeit-Alerting benötigt wird
- [ ] `retention()` explizit gesetzt wenn 30-Tage-Default nicht passt
- [ ] Pest-Test mit `KPI::fake()` für jeden Recording-Pfad
- [ ] Bei `beacon/dashboard`: Tile in Dashboard-Definition ergänzt (siehe Skill `beacon-dashboard-configuration`)
- [ ] Bei sync-Queue: `KpiRecordingMiddleware` global in `bootstrap/app.php` registriert
