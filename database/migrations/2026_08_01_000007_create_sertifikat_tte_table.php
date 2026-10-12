<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi tabel `sertifikat_tte` — Penerbitan & Monitoring Siklus Hidup Sertifikat.
     * Ref: SCHEMA.md §7.6
     */
    public function up(): void
    {
        Schema::create('sertifikat_tte', function (Blueprint $table) {
            $table->id(); // BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY

            // Relasi 1:1 ke pendaftaran_tte
            $table->unsignedBigInteger('pendaftaran_id')->unique();

            $table->string('email_dinas', 255)->unique(); // Format @cirebonkota.go.id
            $table->date('tanggal_penerbitan');
            $table->date('tanggal_kadaluarsa');           // tanggal_penerbitan + 2 tahun
            $table->string('status_sertifikat', 20)->default('AKTIF');

            // Verifikator Tahap 2
            $table->unsignedBigInteger('verified_by_tahap2');
            $table->timestampTz('verified_at_tahap2')->useCurrent();
            $table->string('verified_ip_tahap2', 45)->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // Foreign Key Constraints
            $table->foreign('pendaftaran_id')
                  ->references('id')
                  ->on('pendaftaran_tte')
                  ->restrictOnDelete();

            $table->foreign('verified_by_tahap2')
                  ->references('id')
                  ->on('users')
                  ->restrictOnDelete();
        });

        // CHECK constraint untuk status_sertifikat
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE sertifikat_tte
            ADD CONSTRAINT chk_status_sertifikat
            CHECK (status_sertifikat IN ('AKTIF', 'WARNING_H3', 'EXPIRED', 'REVOKED', 'PERLU_PERPANJANG'))
        ");

        // Composite Index untuk Scheduler Harian H-3 & Expired Scanner
        Schema::table('sertifikat_tte', function (Blueprint $table) {
            $table->index(['status_sertifikat', 'tanggal_kadaluarsa'], 'idx_sertifikat_status_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_tte');
    }
};
