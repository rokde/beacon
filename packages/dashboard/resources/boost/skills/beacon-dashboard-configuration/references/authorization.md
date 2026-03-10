# Autorisierungs-Referenz

## Funktionsprinzip

Für jedes Dashboard wird automatisch ein Laravel Gate registriert:
`beacon.view.{dashboard-id}`

Der `DashboardController` prüft das Gate automatisch. Kein manuelles `abort(403)` nötig.
Bei Ablehnung wird auf die konfigurierbare Fehlerseite umgeleitet.

---

## Häufige Muster

### Rollenbasiert (Spatie Laravel Permission)

```php
->authorize(fn ($user) => $user->hasRole('sales_manager'))

// Mehrere Rollen
->authorize(fn ($user) => $user->hasAnyRole(['admin', 'super_admin']))
```

### Berechtigungsbasiert

```php
->authorize(fn ($user) => $user->hasPermissionTo('view kpi dashboards'))
```

### Policy-basiert

```php
->authorize(fn ($user) => $user->can('view', \App\Models\KpiDashboard::class))
```

### Team-/Organisations-Scope (Multi-Tenant)

```php
->authorize(fn ($user) => $user->currentTeam?->userIsOwner($user) ?? false)
```

### Alle authentifizierten Nutzer

```php
->authorize(fn (?User $user) => $user !== null)
```

### Öffentlich (kein Callback)

`->authorize()` weglassen. Nur für internes Tooling hinter Netzwerk-Auth (VPN, IP-Whitelist).

---

## Null-Safety

Der Callback erhält `null` als `$user` bei unauthentifizierten Anfragen.
Immer auf Null absichern wenn unauthentifizierte Nutzer möglich sind:

```php
// ✓ Korrekt
->authorize(fn (?User $user) => $user !== null && $user->hasRole('analyst'))

// ✗ Fatal Error bei unauthentifizierten Anfragen
->authorize(fn ($user) => $user->hasRole('analyst'))
```

---

## Gate in Blade und Controllern verwenden

```php
// Blade
@can('beacon.view.sales')
    <a href="{{ route('beacon.dashboard.sales') }}">Sales Dashboard</a>
@endcan

// Controller
if (Gate::denies('beacon.view.sales')) {
    abort(403);
}

// Policy / authorize()
$this->authorize('beacon.view.sales');
```

---

## Named Routes

Jedes Dashboard hat eine Named Route: `beacon.dashboard.{id}`

```php
route('beacon.dashboard.sales')   // → /kpi/sales
route('beacon.dashboard.ops')     // → /kpi/ops
```

Immer Named Routes verwenden — niemals die URL hardcoden, da `base_path`
per `KPI_DASHBOARD_PATH` Umgebungsvariable konfigurierbar ist.

---

## Mehrere Dashboards mit verschiedenen Zugriffsebenen

```php
// Öffentliche Zusammenfassung für alle authentifizierten Nutzer
Dashboard::make('public-metrics')
    ->path('/metrics')
    ->authorize(fn (?User $user) => $user !== null)
    ->tiles([...]),

// Sensibel — Finance-Abteilung
Dashboard::make('revenue')
    ->path('/revenue')
    ->authorize(fn (?User $user) => $user?->hasRole('finance') ?? false)
    ->tiles([...]),

// Intern — kein Callback, netzwerkseitig geschützt
Dashboard::make('debug')
    ->path('/debug')
    ->tiles([...]),
```
