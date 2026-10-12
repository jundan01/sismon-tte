<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Partial Unique Indexes pada pendaftaran_tte — PostgreSQL Raw SQL.
     * Mencegah duplikasi data aktif, izinkan submit ulang jika DITOLAK.
     * Index langsung pada kolom nik/nip terenkripsi deterministik.
     * Ref: SCHEMA.md §7.4
     */
    public function up(): void
    {
        // Partial Unique Index: NIK aktif (MENUNGGU_VERIFIKASI + DISETUJUI) harus unik
        DB::statement("
            CREATE UNIQUE INDEX idx_pendaftaran_nik_active
            ON pendaftaran_tte (nik)
            WHERE status != 'DITOLAK'
        ");

        // Partial Unique Index: NIP aktif (MENUNGGU_VERIFIKASI + DISETUJUI) harus unik
        DB::statement("
            CREATE UNIQUE INDEX idx_pendaftaran_nip_active
            ON pendaftaran_tte (nip)
            WHERE status != 'DITOLAK'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_pendaftaran_nik_active');
        DB::statement('DROP INDEX IF EXISTS idx_pendaftaran_nip_active');
    }
};
