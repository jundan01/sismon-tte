<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan default partition fallback untuk tabel `security_audit_logs`.
     * Mencegah kegagalan penyimpanan log audit saat rentang partisi bulanan terlampaui.
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS security_audit_logs_default
            PARTITION OF security_audit_logs DEFAULT;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS security_audit_logs_default CASCADE;');
    }
};
