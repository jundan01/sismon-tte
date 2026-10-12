<?php

namespace App\Jobs;

use App\Domain\Analytics\Models\SecurityAuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Worker Asinkron Pencatatan Audit Trail Keamanan.
 * Mengalirkan log mutasi data secara non-blocking via Redis 7 Queue 'audit-logs'.
 * Ref: SECURITY.md §8.1, ARCHITECTURE.md §10
 */
class ProcessAuditLogJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    /**
     * @param array{
     *     user_id?: int|null,
     *     action_type: string,
     *     entity_name: string,
     *     entity_id?: int|null,
     *     ip_address: string,
     *     user_agent?: string|null,
     *     payload_before?: array|null,
     *     payload_after?: array|null
     * } $data
     */
    public function __construct(
        public array $data
    ) {
        $this->onQueue('audit-logs');
    }

    public function handle(): void
    {
        try {
            SecurityAuditLog::create([
                'user_id'        => $this->data['user_id'] ?? null,
                'action_type'    => $this->data['action_type'],
                'entity_name'    => $this->data['entity_name'],
                'entity_id'      => $this->data['entity_id'] ?? null,
                'ip_address'     => $this->data['ip_address'] ?? '127.0.0.1',
                'user_agent'     => $this->data['user_agent'] ?? null,
                'payload_before' => $this->data['payload_before'] ?? null,
                'payload_after'  => $this->data['payload_after'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessAuditLogJob: Gagal mencatat log audit ke PostgreSQL partisi', [
                'error' => $e->getMessage(),
                'payload' => $this->data,
            ]);

            throw $e; // Memungkinkan retry otomatis sesuai konfigurasi $tries
        }
    }
}
