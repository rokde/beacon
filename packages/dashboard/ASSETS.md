# Beacon Dashboard — Asset Pipeline

## Überblick

```
resources/
├── css/
│   └── beacon.css          ← Tailwind v4 + CSS Custom Properties (Theme-Tokens)
└── ts/
    ├── beacon.ts            ← Haupt-Entry (wired via data-* attributes)
    ├── chart.ts             ← Chart.js (tree-shaken, nur benötigte Komponenten)
    ├── gauge.ts             ← SVG-Gauge (kein JS-Framework, pure DOM)
    ├── theme.ts             ← Dark/Light/System Theme via localStorage
    ├── polling.ts           ← Polling-Refresh ohne Framework
    └── types.ts             ← Shared TypeScript types

dist/                        ← Kompilierte Assets (committed to git!)
├── css/
│   └── beacon.css
└── js/
    └── beacon.js
```

## Voraussetzungen

- Node.js ≥ 22
- npm ≥ 10

## Entwicklung

```bash
cd packages/dashboard

# Dependencies installieren (einmalig)
npm install

# Dev-Build mit Watch + Hot Module Reload
npm run dev

# Production-Build (minified, kein sourcemap)
NODE_ENV=production npm run build

# TypeScript-Typen prüfen (kein Emit)
npm run typecheck
```

## Production-Build & Commit

Die `dist/` Assets werden **committed** — Host-Apps benötigen
keinen eigenen Node-Build-Schritt. Sie führen nur `vendor:publish` aus.

```bash
# 1. Build
NODE_ENV=production npm run build

# 2. Diff prüfen
git diff dist/

# 3. Assets als Teil des Release-Commits einschließen
git add dist/
git commit -m "chore: rebuild assets for vX.Y.Z"
git tag vX.Y.Z
git push origin vX.Y.Z
```

Der GitHub Actions Workflow (`split.yml`) baut die Assets automatisch
vor dem Package-Split, wenn ein Tag gepusht wird.

## Host-App Setup

```bash
# Assets in public/vendor/beacon/ publizieren
php artisan vendor:publish --tag=beacon-dashboard-assets
```

Das Blade-Layout lädt die Assets automatisch via `beacon_asset()`:

```html
<link rel="stylesheet" href="{{ beacon_asset('css/beacon.css') }}">
<script src="{{ beacon_asset('js/beacon.js') }}" defer></script>
```

## Chart.js Tree-Shaking

`chart.ts` registriert nur die tatsächlich verwendeten Chart.js-Komponenten:

```typescript
import {
    Chart,
    CategoryScale, LinearScale,
    PointElement, LineElement, BarElement,
    Filler, Tooltip, Legend,
} from 'chart.js'

Chart.register(/* nur die obigen */)
```

Das reduziert die Chart.js-Bundle-Größe erheblich gegenüber dem
Full-Import (`import Chart from 'chart.js/auto'`).

## CSS-Architektur

Das Design basiert ausschließlich auf **CSS Custom Properties** —
kein JavaScript ändert Farben oder Abstände. Tailwind v4 wird als
utility-generator verwendet, das eigentliche Theme-System ist rein CSS:

```css
:root { --beacon-accent: #6366f1; }           /* Light */
[data-theme="dark"] { --beacon-accent: #818cf8; }  /* Dark */
```

Dark Mode funktioniert damit auch ohne JavaScript (via `prefers-color-scheme`).
Das Theme-Toggle in `theme.ts` setzt nur `data-theme` auf `<html>`.
