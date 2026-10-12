<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom `total_asn` pada tabel `skpd`.
     *
     * Kolom ini menyimpan jumlah total pegawai ASN pada unit kerja terkait,
     * digunakan untuk menghitung persentase capaian adopsi TTE per SKPD
     * pada dashboard analitik Superadmin.
     *
     * Ref: docs/RENCANA_PERUBAHAN_DASHBOARD_TOP_SKPD.md §3.A
     */
    public function up(): void
    {
        Schema::table('skpd', function (Blueprint $table) {
            $table->unsignedInteger('total_asn')
                  ->default(0)
                  ->after('nama_skpd')
                  ->comment('Jumlah total pegawai ASN pada unit kerja ini');
        });
    }

    public function down(): void
    {
        Schema::table('skpd', function (Blueprint $table) {
            $table->dropColumn('total_asn');
        });
    }
};
