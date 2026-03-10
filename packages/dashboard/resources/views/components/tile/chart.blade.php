@props([
    'tile',
    'data',
])

@php
    $currentValue   = $data['current_value'] ?? 0;
    $trendDirection = $data['trend_direction'] ?? 'neutral';
    $trendPct       = $data['trend_pct'] ?? null;
    $compLabel      = $data['comparison_label'] ?? null;
    $isRealtime     = $data['is_realtime'] ?? false;
    $series         = $data['series'] ?? [];
    $forecast       = $data['forecast'] ?? [];
    $height         = $tile->getChartHeight();

    // Build ChartOptions JSON for beacon.ts
    $chartOptions = [
        'type'           => $tile->getChartType() ?? 'line',
        'granularity'    => $tile->getGranularity()->value,
        'showForecast'   => $tile->hasForecast() && count($forecast) > 0,
        'showComparison' => false,
        'datasets'       => [
            [
                'label'    => $tile->getLabel(),
                'data'     => $series,
                'forecast' => $forecast,
            ],
        ],
    ];
    $chartOptionsJson = json_encode($chartOptions, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_THROW_ON_ERROR);

    $formattedValue = number_format($currentValue, is_float($currentValue) && fmod($currentValue, 1) !== 0.0 ? 2 : 0);
@endphp

<article class="beacon-tile {{ $tile->getSize()->cssClass() }}"
         aria-label="{{ $tile->getLabel() }}">

    <div class="beacon-tile__header">
        <div>
            <span class="beacon-tile__label">{{ $tile->getLabel() }}</span>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                <span style="font-size: 1.5rem; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: -0.03em;">
                    {{ $formattedValue }}
                </span>
                @if($trendPct !== null)
                    <span class="beacon-trend beacon-trend--{{ $trendDirection }}">
                        {{ abs($trendPct) }}%
                    </span>
                @endif
            </div>
            @if($compLabel)
                <div style="font-size: 0.6875rem; color: var(--beacon-text-muted); margin-top: 0.125rem;">
                    {{ $compLabel }}
                </div>
            @endif
        </div>

        @if($isRealtime)
            <span class="beacon-tile__badge beacon-tile__badge--live">Live</span>
        @endif
    </div>

    {{-- Chart canvas — data-beacon-chart activates beacon.ts --}}
    @if(count($series) > 0)
        <div class="beacon-chart-wrapper" style="height: {{ $height }}px;">
            <canvas
                    data-beacon-chart="{{ $chartOptionsJson }}"
                    aria-label="{{ $tile->getLabel() }} chart"
                    role="img"
            ></canvas>
        </div>
    @else
        <div class="beacon-empty">No data available</div>
    @endif

</article>
