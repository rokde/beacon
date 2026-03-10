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
        Schema::connection($this->connection)->create('kpi_events', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_key', 64)->index();
            $table->decimal('value', 20, 6);
            $table->timestamp('recorded_at')->useCurrent();
            $table->json('meta')->nullable();

            // Composite index for fast aggregation queries
            $table->index(['kpi_key', 'recorded_at'], 'kpi_events_key_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('kpi_events');
    }
};
