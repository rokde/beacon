@foreach($dashboard->getTiles() as $tile)
    @php $data = $tilesData[$tile->kpiKey] ?? []; @endphp

    @if($tile->getChartType() || $tile->getSize()->value !== 'sm')
        <x-beacon-dashboard::tile.chart
                :tile="$tile"
                :data="$data"
        />
    @elseif($tile->kpiKey && str_contains($tile->kpiKey, 'rate') || str_contains($tile->kpiKey, 'ratio'))
        <x-beacon-dashboard::tile.gauge
                :tile="$tile"
                :data="$data"
        />
    @else
        <x-beacon-dashboard::tile.metric
                :tile="$tile"
                :data="$data"
        />
    @endif
@endforeach
