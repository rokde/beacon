<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | KPI Database Connection
    |--------------------------------------------------------------------------
    | The database connection used for kpi_events and kpi_aggregates tables.
    | Recommendation: use a dedicated connection / separate schema for isolation.
    */
    'connection' => env('KPI_DB_CONNECTION', 'kpi'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    | The queue connection for dispatching RecordKpiEventJob and AggregateKpiJob.
    | When set to "sync": RecordKpiEventJob is deferred to TerminableMiddleware.
    | When set to any other value: jobs are dispatched immediately.
    */
    'queue_connection' => env('KPI_QUEUE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    | All Beacon jobs are dispatched to this named queue.
    | Run a dedicated worker: php artisan queue:work --queue=kpi
    */
    'queue_name' => env('KPI_QUEUE_NAME', 'kpi'),

    /*
    |--------------------------------------------------------------------------
    | Aggregation Interval (minutes)
    |--------------------------------------------------------------------------
    | How often the AggregateAllKpisJob is scheduled. Min: 1, Max: 60.
    | A lower value means fresher dashboard data, at the cost of more DB load.
    */
    'aggregation_interval' => (int) env('KPI_AGGREGATION_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Default Retention (days)
    |--------------------------------------------------------------------------
    | How long raw kpi_events are retained before being deleted after aggregation.
    | Can be overridden per-KPI via KpiDefinition::retention().
    */
    'retention_days' => (int) env('KPI_RETENTION_DAYS', 30),
];
