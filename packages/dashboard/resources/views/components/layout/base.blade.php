<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ cookie('beacon-theme', 'light') }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? config('kpi-dashboard.app_name', 'Beacon') }} · {{ config('app.name') }}</title>

    {{-- Preload fonts (system stack — no external requests) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">

    {{-- Beacon compiled CSS --}}
    <link rel="stylesheet" href="{{ beacon_asset('css/beacon.css') }}">

    @stack('head')
</head>
<body class="beacon-layout">

{{ $slot }}

{{-- Beacon compiled JS (deferred) --}}
<script src="{{ beacon_asset('js/beacon.js') }}" defer></script>

@stack('scripts')
</body>
</html>
