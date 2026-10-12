<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyesuaian batas waktu dan peringatan kedaluwarsa sertifikat TTE dari H-3 menjadi H-7.
     */
    public function up(): void
    {
        // 1. Perbarui CHECK constraint pada tabel sertifikat_tte
        DB::statement("ALTER TABLE sertifikat_tte DROP CONSTRAINT IF EXISTS chk_status_sertifikat;");
        DB::statement("
            ALTER TABLE sertifikat_tte
            ADD CONSTRAINT chk_status_sertifikat
            CHECK (status_sertifikat IN ('AKTIF', 'WARNING_H7', 'WARNING_H3', 'EXPIRED', 'REVOKED', 'PERLU_PERPANJANG'));
        ");

        // 2. Migrasikan status sertifikat lama jika ada
        DB::table('sertifikat_tte')
            ->where('status_sertifikat', 'WARNING_H3')
            ->update(['status_sertifikat' => 'WARNING_H7']);

        // 3. Tambahkan kolom total_warning_h7 pada tabel dashboard_daily_stats jika belum ada
        if (!Schema::hasColumn('dashboard_daily_stats', 'total_warning_h7')) {
            Schema::table('dashboard_daily_stats', function (Blueprint $table) {
                $table->bigInteger('total_warning_h7')->default(0)->after('total_warning_h3');
            });

            // Isi nilai awal dari total_warning_h3
            DB::statement("UPDATE dashboard_daily_stats SET total_warning_h7 = total_warning_h3;");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dashboard_daily_stats', 'total_warning_h7')) {
            Schema::table('dashboard_daily_stats', function (Blueprint $table) {
                $table->dropColumn('total_warning_h7');
            });
        }

        DB::table('sertifikat_tte')
            ->where('status_sertifikat', 'WARNING_H7')
            ->update(['status_sertifikat' => 'WARNING_H3']);

        DB::statement("ALTER TABLE sertifikat_tte DROP CONSTRAINT IF EXISTS chk_status_sertifikat;");
        DB::statement("
            ALTER TABLE sertifikat_tte
            ADD CONSTRAINT chk_status_sertifikat
            CHECK (status_sertifikat IN ('AKTIF', 'WARNING_H3', 'EXPIRED', 'REVOKED', 'PERLU_PERPANJANG'));
        ");
    }
};
