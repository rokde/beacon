<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'kpi';

    public function up(): void
    {
        Schema::connection($this->connection)->create('kpi_aggregates', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('kpi_key', 64);
            $blueprint->string('granularity', 10);
            $blueprint->timestamp('period_start');
            $blueprint->decimal('value', 20, 6)->default(0);
            $blueprint->unsignedInteger('count')->default(0);
            $blueprint->json('meta')->nullable();
            $blueprint->timestamps();

            // Unique constraint ensures upsert idempotency
            $blueprint->unique(
                ['kpi_key', 'granularity', 'period_start'],
                'kpi_aggregates_unique_period',
            );

            // Index for dashboard queries (range scans by kpi + granularity + time)
            $blueprint->index(
                ['kpi_key', 'granularity', 'period_start'],
                'kpi_aggregates_query_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('kpi_aggregates');
    }
};
