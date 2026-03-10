@props([
    'tile',   // TileDefinition
    'data',   // array from QueryEngine
])

@php
    $currentValue   = $data['current_value'] ?? 0;
    $previousValue  = $data['previous_value'] ?? null;
    $trendDirection = $data['trend_direction'] ?? 'neutral';
    $trendPct       = $data['trend_pct'] ?? null;
    $compLabel      = $data['comparison_label'] ?? null;
    $isRealtime     = $data['is_realtime'] ?? false;
    $series         = $data['series'] ?? [];

    $formattedValue = number_format($currentValue, is_float($currentValue) && fmod($currentValue, 1) !== 0.0 ? 2 : 0);
@endphp

<article class="beacon-tile {{ $tile->getSize()->cssClass() }}"
         aria-label="{{ $tile->getLabel() }}: {{ $formattedValue }}">

    <div class="beacon-tile__header">
        <span class="beacon-tile__label">{{ $tile->getLabel() }}</span>

        @if($isRealtime)
            <span class="beacon-tile__badge beacon-tile__badge--live" aria-label="Live data">Live</span>
        @endif
    </div>

    <div class="beacon-metric__value {{ $tile->getSize()->value === 'sm' ? 'beacon-metric__value--sm' : '' }}">
        {{ $formattedValue }}
    </div>

    @if($trendPct !== null || $compLabel !== null)
        <div class="beacon-metric__comparison">
            @if($trendPct !== null)
                <span class="beacon-trend beacon-trend--{{ $trendDirection }}"
                      aria-label="{{ $trendDirection === 'up' ? 'Up' : ($trendDirection === 'down' ? 'Down' : 'No change') }} {{ abs($trendPct) }}%">
                    {{ abs($trendPct) }}%
                </span>
            @endif

            @if($compLabel)
                <span>{{ $compLabel }}</span>
                @if($previousValue !== null)
                    <span>({{ number_format($previousValue, is_float($previousValue) && fmod($previousValue, 1) !== 0.0 ? 2 : 0) }})</span>
                @endif
            @endif
        </div>
    @endif

    {{-- Sparkline — pure SVG, no JS, server-side path generation --}}
    @if(count($series) >= 3 && $tile->getSize()->value !== 'sm')
        <x-beacon-dashboard::partials.sparkline :series="$series" />
    @endif

</article>
