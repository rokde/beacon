# Tile-Typen Referenz

## Tile-Rendering nach KpiType

| KpiType | Tile-Darstellung | Empfohlene Größe |
|---|---|---|
| `SimpleCounter` | MetricTile: Zahl + Trendpfeil + Sparkline | Small oder Medium |
| `DecrementCounter` | MetricTile: Netto-Zahl + Trendpfeil | Small oder Medium |
| `Gauge` | ChartTile `type: line` — Verlauf über Zeit | Large |
| `Rate` | ChartTile `type: line` | Medium oder Large |
| `Ratio` | GaugeTile: Radial-Gauge (0–100 %) | Small |
| `Duration` | ChartTile `type: line` | Medium |

---

## MetricTile

Gerendert wenn kein `->chart()`-Aufruf und `TileSize::Small` oder `TileSize::Medium`.

Zeigt:
- Große Zahl (Summe/letzter Wert der aktuellen Periode)
- Trendpfeil (↑ grün / ↓ rot / → neutral) mit Prozentveränderung
- Vergleichswert und -label
- SVG-Sparkline (server-seitig gerendert, ohne JS — ab Medium)

```php
Tile::kpi('new_registrations')
    ->label('Neue Registrierungen')
    ->size(TileSize::Medium)
    ->comparison(Comparison::previousPeriod())
```

---

## ChartTile

Gerendert wenn `->chart()` aufgerufen oder `TileSize::Large`.

Zeigt:
- Metrik-Zusammenfassung im Header
- Chart.js Linien- oder Balkendiagramm
- Optional gestrichelte Prognoselinie mit Konfidenzband

**Linien-Chart** (`type: 'line'`): für `Gauge`, `Rate`, `Duration`, `Ratio` —
wenn der Verlauf über Zeit wichtig ist.

```php
Tile::kpi('mrr')
    ->label('MRR')
    ->size(TileSize::Large)
    ->chart('line', 240)
    ->comparison(Comparison::offset('-1 month'))
    ->showForecast(horizon: '90 days')
```

**Balken-Chart** (`type: 'bar'`): für `SimpleCounter` — wenn das Volumen pro
Periode nebeneinander verglichen werden soll.

```php
Tile::kpi('daily_orders')
    ->label('Tägliche Bestellungen')
    ->size(TileSize::Large)
    ->chart('bar', 200)
    ->granularity(Granularity::Day)
    ->period(30)
```

---

## GaugeTile

Automatisch gerendert für `Ratio`-KPIs bei `TileSize::Small`.

Zeigt:
- SVG-Halbkreisbogen (server-seitig gerendert, JS-enhanced für Animation)
- Farbe: grün unter 60 %, amber 60–85 %, rot über 85 %
- Wert in der Mitte

```php
Tile::kpi('conversion_rate')
    ->label('Conversion Rate')
    ->size(TileSize::Small)
```

---

## Raster-Layout

Das Dashboard nutzt ein 4-Spalten-Raster (responsiv):

```
Desktop (4 Spalten):
  [Small][Small][Small][Small]
  [Medium           ][Medium]
  [Large                    ]

Tablet (2 Spalten):
  [Small][Small]
  [Medium      ]
  [Large       ]

Mobile (1 Spalte):
  [Small]
  [Medium]
  [Large]
```

**Praxishinweis Tile-Größen:**

`TileSize::Small` — eine einzelne KPI, kein Chart. Ideal für Kennzahlen am
Dashborad-Anfang die sofort lesbar sein sollen (z.B. Churn Rate, Fehlerquote).

`TileSize::Medium` — der Standard. Zeigt Sparkline + einen Vergleich.
Kein vollständiger Chart außer `->chart()` ist explizit gesetzt.

`TileSize::Large` — Vollbreite. Verwenden wenn der Zeitverlauf wichtig ist,
besonders mit aktivierter Prognose (Horizon ≥ 30 Tage).

---

## Granularität pro Tile überschreiben

Tiles erben die Default-Granularität der KpiDefinition.
Überschreiben wenn die Dashboard-Zeitauflösung abweicht:

```php
// Stündlich für Intraday-Dashboard (letzte 48 Stunden)
Tile::kpi('api_errors')
    ->granularity(Granularity::Hour)
    ->period(2)

// Monatlich für Executive-Dashboard (letztes Jahr)
Tile::kpi('mrr')
    ->granularity(Granularity::Month)
    ->period(365)
```

**Wichtig:** Die angeforderte Granularität muss in der `->granularities([...])`-
Liste der KpiDefinition konfiguriert sein. Fehlt sie, rendert das Tile leer
mit der Meldung „No data available".
