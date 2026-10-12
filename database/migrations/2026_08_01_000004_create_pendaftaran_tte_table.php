<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi tabel `pendaftaran_tte` — Transaksi Utama Pendaftaran TTE.
     * Ref: SCHEMA.md §7.4
     */
    public function up(): void
    {
        Schema::create('pendaftaran_tte', function (Blueprint $table) {
            $table->id(); // BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY
            $table->string('nomor_referensi', 64)->unique(); // Referensi Internal Superadmin Only
            $table->string('nama_lengkap', 255);
            $table->string('nik', 255);  // Enkripsi deterministik AES-256-CBC (Base64)
            $table->string('nip', 255);  // Enkripsi deterministik AES-256-CBC (Base64)
            $table->string('no_hp', 30);

            // Foreign Keys ke master tables
            $table->unsignedBigInteger('skpd_id');
            $table->unsignedBigInteger('jabatan_id')->nullable();
            $table->unsignedBigInteger('golongan_id')->nullable();

            // Alur Email Dinas
            $table->boolean('status_memiliki_email')->default(false);
            $table->string('email_existing', 255)->nullable();
            $table->string('email_usulan', 255)->nullable();     // Auto-generate @cirebonkota.go.id
            $table->string('email_final_terverifikasi', 255)->nullable(); // Definitif Superadmin
            $table->boolean('is_email_recap_confirmed')->default(false);

            // Status & Verifikasi
            $table->string('status', 30)->default('MENUNGGU_VERIFIKASI');
            $table->text('catatan_penolakan')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->string('verified_ip', 45)->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // Foreign Key Constraints
            $table->foreign('skpd_id')->references('id')->on('skpd')->restrictOnDelete();
            $table->foreign('jabatan_id')->references('id')->on('master_jabatan_golongan')->restrictOnDelete();
            $table->foreign('golongan_id')->references('id')->on('master_jabatan_golongan')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        // CHECK constraint untuk status
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE pendaftaran_tte
            ADD CONSTRAINT chk_pendaftaran_status CHECK (status IN ('MENUNGGU_VERIFIKASI', 'DISETUJUI', 'DITOLAK'))
        ");

        // Index komposit status + created_at untuk query antrian
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_pendaftaran_status_created');
            $table->index('skpd_id', 'idx_pendaftaran_skpd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_tte');
    }
};
