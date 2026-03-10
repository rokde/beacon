<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | KPI Database Connection
    |--------------------------------------------------------------------------
    | Must match the connection configured in kpi-recorder.php.
    */
    'connection' => env('KPI_DB_CONNECTION', 'kpi'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Base Path
    |--------------------------------------------------------------------------
    | All dashboard routes are prefixed with this path.
    | Example: '/kpi' → dashboard 'sales' is at /kpi/sales
    */
    'base_path' => env('KPI_DASHBOARD_PATH', '/kpi'),

    /*
    |--------------------------------------------------------------------------
    | Asset URL
    |--------------------------------------------------------------------------
    | Base URL for beacon's compiled CSS/JS assets.
    | Default: uses Laravel's asset() helper pointing to /vendor/beacon/
    */
    'asset_url' => env('KPI_ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    | Shown in the browser title bar alongside the dashboard name.
    */
    'app_name' => env('KPI_APP_NAME', 'Beacon'),

    /*
    |--------------------------------------------------------------------------
    | Default Refresh Interval (seconds)
    |--------------------------------------------------------------------------
    | Used when a dashboard does not explicitly set refreshInterval().
    */
    'refresh_interval' => (int) env('KPI_REFRESH_INTERVAL', 300),
];
