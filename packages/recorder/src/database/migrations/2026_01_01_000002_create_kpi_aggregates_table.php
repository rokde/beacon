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
        Schema::connection($this->connection)->create('kpi_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_key', 64);
            $table->string('granularity', 10);
            $table->timestamp('period_start');
            $table->decimal('value', 20, 6)->default(0);
            $table->unsignedInteger('count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            // Unique constraint ensures upsert idempotency
            $table->unique(
                ['kpi_key', 'granularity', 'period_start'],
                'kpi_aggregates_unique_period',
            );

            // Index for dashboard queries (range scans by kpi + granularity + time)
            $table->index(
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
