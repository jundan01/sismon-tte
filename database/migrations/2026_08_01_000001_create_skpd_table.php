<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi tabel `skpd` — Master Unit Kerja / Instansi Kedinasan.
     * Ref: SCHEMA.md §7.1
     */
    public function up(): void
    {
        Schema::create('skpd', function (Blueprint $table) {
            $table->id(); // BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY
            $table->string('kode_skpd', 50)->unique();
            $table->string('nama_skpd', 255);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // Index untuk pencarian nama SKPD
        Schema::table('skpd', function (Blueprint $table) {
            $table->index('nama_skpd', 'idx_skpd_nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skpd');
    }
};
