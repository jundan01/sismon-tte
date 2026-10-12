<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Merevisi penambahan sub kategori: menghapus field sub_kategori_asn & sub_unit_kerja,
     * serta menambahkan field deskripsi pada pendaftaran_tte.
     */
    public function up(): void
    {
        // 1. Tambahkan kolom deskripsi jika belum ada
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            if (!Schema::hasColumn('pendaftaran_tte', 'deskripsi')) {
                $table->text('deskripsi')->nullable()->after('skpd_id');
            }
        });

        // 2. Drop indeks sub kategori jika ada
        try {
            DB::statement('DROP INDEX IF EXISTS idx_pendaftaran_skpd_sub_kategori');
            DB::statement('DROP INDEX IF EXISTS idx_pendaftaran_skpd_sub_unit');
        } catch (\Throwable $e) {
            // Abaikan jika indeks tidak ditemukan di driver non-PostgreSQL
        }

        // 3. Hapus kolom sub_kategori_asn dan sub_unit_kerja
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('pendaftaran_tte', 'sub_kategori_asn')) {
                $columnsToDrop[] = 'sub_kategori_asn';
            }
            if (Schema::hasColumn('pendaftaran_tte', 'sub_unit_kerja')) {
                $columnsToDrop[] = 'sub_unit_kerja';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            if (!Schema::hasColumn('pendaftaran_tte', 'sub_kategori_asn')) {
                $table->string('sub_kategori_asn', 100)->default('DINAS_INDUK')->after('skpd_id');
            }
            if (!Schema::hasColumn('pendaftaran_tte', 'sub_unit_kerja')) {
                $table->string('sub_unit_kerja', 255)->nullable()->default('Kantor Induk')->after('sub_kategori_asn');
            }
            if (Schema::hasColumn('pendaftaran_tte', 'deskripsi')) {
                $table->dropColumn('deskripsi');
            }
        });
    }
};
