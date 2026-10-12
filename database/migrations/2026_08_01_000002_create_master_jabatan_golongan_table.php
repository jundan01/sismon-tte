<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi tabel `master_jabatan_golongan` — Master Jabatan & Golongan ASN.
     * Ref: SCHEMA.md §7.2
     */
    public function up(): void
    {
        Schema::create('master_jabatan_golongan', function (Blueprint $table) {
            $table->id(); // BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY
            $table->string('kategori', 20); // 'JABATAN' atau 'GOLONGAN'
            $table->string('kode', 50)->unique();
            $table->string('nama_label', 100);
            $table->timestampTz('created_at')->useCurrent();
        });

        // CHECK constraint untuk kategori
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE master_jabatan_golongan
            ADD CONSTRAINT chk_kategori CHECK (kategori IN ('JABATAN', 'GOLONGAN'))
        ");

        // Index untuk filter berdasarkan kategori
        Schema::table('master_jabatan_golongan', function (Blueprint $table) {
            $table->index('kategori', 'idx_master_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_jabatan_golongan');
    }
};
