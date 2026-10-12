<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi tabel `dashboard_daily_stats` — Fast Aggregator Statistik Harian.
     * Ref: SCHEMA.md §7.7
     */
    public function up(): void
    {
        Schema::create('dashboard_daily_stats', function (Blueprint $table) {
            $table->id(); // BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY
            $table->date('stat_date')->unique();
            $table->bigInteger('total_asn')->default(0);
            $table->bigInteger('total_terbit')->default(0);
            $table->bigInteger('total_antrian')->default(0);
            $table->bigInteger('total_warning_h3')->default(0);
            $table->timestampTz('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_daily_stats');
    }
};
