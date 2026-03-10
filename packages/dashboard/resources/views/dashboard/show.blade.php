<x-beacon-dashboard::layout.base :title="$dashboard->getLabel()">

    {{-- Header --}}
    <header class="beacon-header">
        <div>
            <h1 class="beacon-header__title">{{ $dashboard->getLabel() }}</h1>
            <p class="beacon-header__meta">
                <span class="beacon-refresh-indicator" id="beacon-refresh-indicator">
                    Updated {{ now()->format('H:i') }}
                </span>
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <button
                    class="beacon-theme-toggle"
                    data-beacon-theme-toggle
                    aria-label="Toggle theme"
                    type="button"
            >☾</button>
        </div>
    </header>

    {{-- Tile grid — polling target --}}
    <main class="beacon-main">
        <div
                class="beacon-grid"
                data-beacon-poll="{{ $dashboard->getRefreshInterval() }}"
                data-beacon-poll-url="{{ url()->current() }}"
                data-beacon-poll-target=".beacon-grid"
                id="beacon-grid"
        >
            @include('beacon-dashboard::partials.grid', ['dashboard' => $dashboard, 'tilesData' => $tilesData])
        </div>
    </main>

</x-beacon-dashboard::layout.base>
