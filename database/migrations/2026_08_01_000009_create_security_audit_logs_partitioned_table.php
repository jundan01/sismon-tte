<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrasi tabel `security_audit_logs` — Native Monthly Range Partitioning.
     * Menggunakan raw SQL karena Laravel Blueprint tidak mendukung PARTITION BY.
     * Ref: SCHEMA.md §7.8
     */
    public function up(): void
    {
        // Tabel utama terpartisi
        DB::statement("
            CREATE TABLE security_audit_logs (
                id BIGINT GENERATED ALWAYS AS IDENTITY,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                user_id BIGINT NULL,
                action_type VARCHAR(50) NOT NULL,
                entity_name VARCHAR(50) NOT NULL,
                entity_id BIGINT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                payload_before JSONB NULL,
                payload_after JSONB NULL,
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        ");

        // GIN Index untuk pencarian cepat atribut JSONB
        DB::statement("
            CREATE INDEX idx_audit_payload_after_gin
            ON security_audit_logs USING GIN (payload_after)
        ");

        // Index komposit user_id + action_type
        DB::statement("
            CREATE INDEX idx_audit_user_action
            ON security_audit_logs (user_id, action_type)
        ");

        // Partisi bulan saat ini dan bulan berikutnya
        $currentMonth = now()->startOfMonth();
        for ($i = 0; $i < 3; $i++) {
            $start = $currentMonth->copy()->addMonths($i);
            $end = $start->copy()->addMonth();
            $partitionName = 'security_audit_logs_' . $start->format('Y_m');

            DB::statement("
                CREATE TABLE {$partitionName}
                PARTITION OF security_audit_logs
                FOR VALUES FROM ('{$start->toDateTimeString()}') TO ('{$end->toDateTimeString()}')
            ");
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS security_audit_logs CASCADE');
    }
};
