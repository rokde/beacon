# KPI Type Referenz

Die Wahl des `KpiType` bestimmt die Aggregationslogik.
Ein falscher Typ kann nicht rückwirkend korrigiert werden
ohne alle historischen Daten neu zu aggregieren.

---

## SimpleCounter

**Aggregation:** SUM aller Rohwerte im Zeitfenster

Verwenden wenn:
- Das Ereignis nur hochzählt, nie herunterzählt
- „Wie viele X sind heute passiert?"

```php
// Registrierungen zählen
KpiDefinition::make('new_registrations')
    ->type(KpiType::SimpleCounter)
    ->listenOn(UserRegistered::class, fn ($e) => 1);

// Umsatz summieren
KpiDefinition::make('order_revenue')
    ->type(KpiType::SimpleCounter)
    ->listenOn(OrderCompleted::class, fn ($e) => $e->order->total);

// Seitenaufrufe zählen (imperativ, z.B. im Controller)
KPI::record('page_views', 1);
```

Praxisbeispiele: Registrierungen, Käufe, Seitenaufrufe, API-Aufrufe, Fehler.

---

## DecrementCounter

**Aggregation:** NET-SUM — positive Werte addieren, negative subtrahieren

Verwenden wenn:
- Das Ereignis hoch und runter gehen kann
- „Wie ist die Nettoveränderung in diesem Zeitraum?"

```php
KpiDefinition::make('active_sessions')
    ->type(KpiType::DecrementCounter)
    ->listenOn(SessionStarted::class, fn ($e) => 1)
    ->listenOn(SessionEnded::class, fn ($e) => -1);

KpiDefinition::make('open_tickets')
    ->type(KpiType::DecrementCounter)
    ->listenOn(TicketOpened::class, fn ($e) => 1)
    ->listenOn(TicketClosed::class, fn ($e) => -1);
```

Praxisbeispiele: offene Tickets, aktive Sessions, Warenkorb-Positionen, Inventardeltas.

---

## Gauge

**Aggregation:** LETZTER Wert im Zeitfenster (kein SUM)

Verwenden wenn:
- Ein absoluter Zustand zu einem Zeitpunkt festgehalten werden soll
- Der Wert einen aktuellen Stand repräsentiert, keinen Delta

```php
// Meist imperativ via Scheduled Job aufgezeichnet:
KpiDefinition::make('mrr')
    ->type(KpiType::Gauge)
    ->granularities([Granularity::Day, Granularity::Month])
    ->retention(365);

// Im Scheduler:
KPI::record('mrr', $stripe->getCurrentMrr());

// User-Count nach jeder Änderung:
KpiDefinition::make('subscriber_count')
    ->type(KpiType::Gauge)
    ->listenOn(UserSubscribed::class, fn ($e) => User::active()->count())
    ->listenOn(UserCancelled::class, fn ($e) => User::active()->count());
```

Praxisbeispiele: MRR, Abonnenten-Anzahl, Datenbankzeilen, Lagerbestand, Queue-Tiefe.

---

## Rate

**Aggregation:** AVG von (Wert / Zeiteinheit)

Verwenden wenn:
- Durchsatz gemessen wird (X pro Minute/Stunde)

```php
KpiDefinition::make('api_requests')
    ->type(KpiType::Rate)
    ->granularities([Granularity::Minute, Granularity::Hour])
    ->listenOn(ApiRequestHandled::class, fn ($e) => 1);
```

Achtung: `Granularity::Minute` erhöht das Speichervolumen auf ~525.000 Zeilen/Jahr/KPI.

Praxisbeispiele: Requests/Sekunde, Transaktionen/Minute, E-Mails/Stunde.

---

## Ratio

**Aggregation:** AVG der Quotienten-Werte (0–100)

Verwenden wenn:
- Die Metrik ein Verhältnis oder Prozentsatz ist
- Das Dashboard-Tile als Radial-Gauge (0–100 %) gerendert werden soll

```php
KpiDefinition::make('conversion_rate')
    ->type(KpiType::Ratio)
    ->granularities([Granularity::Day, Granularity::Week]);

// Per Scheduled Job:
$rate = $conversions > 0 ? ($conversions / $visitors) * 100 : 0.0;
KPI::record('conversion_rate', $rate);
```

**Wichtig:** Als 0–100 speichern (nicht 0–1).

Praxisbeispiele: Conversion Rate, Churn Rate, Cache Hit Rate, E-Mail-Öffnungsrate.

---

## Duration

**Aggregation:** AVG der Dauern in Sekunden

Verwenden wenn:
- Zeitspannen zwischen zwei Ereignissen gemessen werden

```php
KpiDefinition::make('onboarding_duration')
    ->type(KpiType::Duration)
    ->listenOn(OnboardingCompleted::class, fn ($e) => $e->durationSeconds);

// Response Time: Millisekunden → Sekunden umrechnen
KpiDefinition::make('api_p50_response_time')
    ->type(KpiType::Duration)
    ->listenOn(ApiRequestHandled::class, fn ($e) => $e->responseTimeMs / 1000);
```

Praxisbeispiele: Onboarding-Dauer, API-Response-Time, Time-to-First-Purchase.

---

## Granularitäten-Referenz

| Granularität | Zeilen/Jahr/KPI | Empfehlung |
|---|---|---|
| `Minute` | ~525.600 | **Opt-in** — nur für Echtzeit-Alerting |
| `Hour` | ~8.760 | Intraday-Monitoring |
| `Day` | 365 | **Standard** — die meisten Dashboards |
| `Week` | 52 | Trend-Analyse |
| `Month` | 12 | Executive-Reporting |
| `Year` | 1 | Langzeit-Trend |

Default wenn `->granularities()` nicht aufgerufen wird: `[Hour, Day, Week, Month, Year]`
