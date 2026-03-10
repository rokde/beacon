<?php

declare(strict_types=1);

if (! function_exists('beacon_asset')) {
    /**
     * Generate a URL to a Beacon compiled asset.
     *
     * Looks in (in order):
     *   1. config('kpi-dashboard.asset_url') — custom CDN/override
     *   2. public/vendor/beacon/  — after vendor:publish
     *   3. Falls back to serving from the package dist/ directly (dev only)
     */
    function beacon_asset(string $path): string
    {
        $base = config('kpi-dashboard.asset_url');

        if (is_string($base) && $base !== '') {
            return rtrim($base, '/').'/'.ltrim($path, '/');
        }

        return asset('vendor/beacon/'.ltrim($path, '/'));
    }
}
