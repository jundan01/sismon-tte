<?php

namespace App\Domain\Pendaftaran\Observers;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Jobs\ProcessAuditLogJob;

/**
 * Observer Pencatat Mutasi Transaksi Pendaftaran TTE ke Redis Queue.
 * Ref: SECURITY.md §8.1, ARCHITECTURE.md §8
 */
class AuditObserver
{
    /**
     * Catat event penambahan pendaftaran baru.
     */
    public function created(PendaftaranTte $pendaftaran): void
    {
        ProcessAuditLogJob::dispatch([
            'user_id'        => auth()->id(),
            'action_type'    => 'CREATE_PENDAFTARAN',
            'entity_name'    => 'pendaftaran_tte',
            'entity_id'      => $pendaftaran->id,
            'ip_address'     => request()->ip() ?? '127.0.0.1',
            'user_agent'     => request()->userAgent(),
            'payload_before' => null,
            'payload_after'  => [
                'nomor_referensi'       => $pendaftaran->nomor_referensi,
                'nama_lengkap'          => $pendaftaran->nama_lengkap,
                'skpd_id'               => $pendaftaran->skpd_id,
                'status_memiliki_email' => $pendaftaran->status_memiliki_email,
                'email_usulan'          => $pendaftaran->email_usulan,
                'status'                => $pendaftaran->status,
            ],
        ]);
    }

    /**
     * Catat event perubahan status / data pendaftaran (misal Verifikasi / Rekap Email).
     */
    public function updated(PendaftaranTte $pendaftaran): void
    {
        $dirty = $pendaftaran->getDirty();
        
        // Buang data mentah sensitif jika ada perubahan sebelum dimasukkan ke payload audit
        unset($dirty['nik'], $dirty['nip']);

        $before = [];
        foreach (array_keys($dirty) as $key) {
            $before[$key] = $pendaftaran->getOriginal($key);
        }

        ProcessAuditLogJob::dispatch([
            'user_id'        => auth()->id(),
            'action_type'    => 'UPDATE_PENDAFTARAN',
            'entity_name'    => 'pendaftaran_tte',
            'entity_id'      => $pendaftaran->id,
            'ip_address'     => request()->ip() ?? '127.0.0.1',
            'user_agent'     => request()->userAgent(),
            'payload_before' => $before,
            'payload_after'  => $dirty,
        ]);
    }
}
