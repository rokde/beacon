@props([
    'series', // list<array{period_start: string, value: float}>
])

@php
    $values = array_column($series, 'value');
    $count  = count($values);

    if ($count < 2) {
        // Nothing to render
        return;
    }

    $min = min($values);
    $max = max($values);
    $range = $max - $min;

    // SVG viewport: 200 wide, 32 tall, with 2px padding
    $svgW  = 200;
    $svgH  = 32;
    $pad   = 2;
    $plotW = $svgW - $pad * 2;
    $plotH = $svgH - $pad * 2;

    // Map values to SVG coordinates
    $points = [];
    foreach ($values as $i => $v) {
        $x = $pad + ($i / ($count - 1)) * $plotW;
        // Invert Y (SVG 0 is top)
        $y = $range > 0
            ? $pad + $plotH - (($v - $min) / $range) * $plotH
            : $pad + $plotH / 2;
        $points[] = [round($x, 2), round($y, 2)];
    }

    // Build polyline points string
    $polylinePoints = implode(' ', array_map(fn ($p) => "{$p[0]},{$p[1]}", $points));

    // Build area polygon (close path along bottom)
    $areaPoints = $polylinePoints
        . " {$points[$count - 1][0]},{$svgH} {$points[0][0]},{$svgH}";
@endphp

<svg
        class="beacon-sparkline"
        viewBox="0 0 {{ $svgW }} {{ $svgH }}"
        preserveAspectRatio="none"
        aria-hidden="true"
        style="margin-top: 0.75rem;"
>
    {{-- Filled area --}}
    <polygon
            class="beacon-sparkline__area"
            points="{{ $areaPoints }}"
    />
    {{-- Line --}}
    <polyline
            class="beacon-sparkline__line"
            points="{{ $polylinePoints }}"
    />
    {{-- Last value dot --}}
    @php [$lx, $ly] = $points[$count - 1]; @endphp
    <circle
            cx="{{ $lx }}"
            cy="{{ $ly }}"
            r="2.5"
            fill="var(--beacon-accent)"
    />
</svg>
