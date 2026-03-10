@props([
    'tile',
    'data',
])

@php
    $value          = $data['current_value'] ?? 0;
    $trendDirection = $data['trend_direction'] ?? 'neutral';
    $trendPct       = $data['trend_pct'] ?? null;
    $isRealtime     = $data['is_realtime'] ?? false;

    // Clamp 0–100 for ratio KPIs
    $pct = min(100, max(0, (float) $value));

    $gaugeOptions = json_encode([
        'value'      => $pct,
        'min'        => 0,
        'max'        => 100,
        'thresholds' => ['warning' => 60, 'danger' => 85],
    ], JSON_THROW_ON_ERROR);
@endphp

<article class="beacon-tile {{ $tile->getSize()->cssClass() }}"
         aria-label="{{ $tile->getLabel() }}: {{ round($pct) }}%">

    <div class="beacon-tile__header">
        <span class="beacon-tile__label">{{ $tile->getLabel() }}</span>

        @if($isRealtime)
            <span class="beacon-tile__badge beacon-tile__badge--live">Live</span>
        @endif
    </div>

    <div class="beacon-gauge">
        {{-- JS renders the SVG arc into this container --}}
        <div data-beacon-gauge="{{ $gaugeOptions }}"
             style="width: 120px; height: 72px;"
             aria-hidden="true">
            {{-- Server-rendered fallback (no JS) --}}
            <svg viewBox="0 0 100 60" role="img" aria-hidden="true">
                @php
                    $r     = 40;
                    $cx    = 50;
                    $cy    = 50;
                    $angle = -M_PI + (M_PI * ($pct / 100));
                    $x     = $cx + $r * cos($angle);
                    $y     = $cy + $r * sin($angle);
                    $large = ($pct > 50) ? 1 : 0;
                    $startX = $cx + $r * cos(-M_PI);
                    $startY = $cy + $r * sin(-M_PI);
                    $color  = $pct >= 85 ? 'var(--beacon-danger)' : ($pct >= 60 ? 'var(--beacon-warning)' : 'var(--beacon-success)');
                @endphp
                {{-- Track --}}
                <path d="M {{ $startX }} {{ $startY }} A {{ $r }} {{ $r }} 0 1 1 {{ $cx + $r }} {{ $cy }}"
                      fill="none"
                      stroke="var(--beacon-border)"
                      stroke-width="8"
                      stroke-linecap="round" />
                {{-- Value arc (only if > 0) --}}
                @if($pct > 0)
                    <path d="M {{ $startX }} {{ $startY }} A {{ $r }} {{ $r }} 0 {{ $large }} 1 {{ round($x, 2) }} {{ round($y, 2) }}"
                          fill="none"
                          stroke="{{ $color }}"
                          stroke-width="8"
                          stroke-linecap="round" />
                @endif
                <text x="50" y="48" text-anchor="middle" dominant-baseline="middle"
                      font-size="14" font-weight="700"
                      fill="var(--beacon-text-primary)">{{ round($pct) }}%</text>
            </svg>
        </div>

        @if($trendPct !== null)
            <span class="beacon-trend beacon-trend--{{ $trendDirection }}">
                {{ abs($trendPct) }}%
            </span>
        @endif
    </div>

</article>
