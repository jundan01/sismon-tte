<?php

namespace App\Domain\Pendaftaran\Actions;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use Illuminate\Validation\ValidationException;

/**
 * Action Penolakan Berkas Pendaftaran Tahap 1.
 * Mengubah status menjadi DITOLAK dengan catatan penolakan wajib.
 * Memungkinkan NIK/NIP didaftarkan ulang melalui partial unique index.
 * Ref: PRD.md §7.2, SCHEMA.md §7.4
 */
class RejectPendaftaranAction
{
    /**
     * Eksekusi penolakan pendaftaran.
     */
    public function execute(PendaftaranTte $pendaftaran, string $catatanPenolakan, int $verifiedByUserId, ?string $ipAddress = null): PendaftaranTte
    {
        if ($pendaftaran->status !== 'MENUNGGU_VERIFIKASI') {
            throw ValidationException::withMessages([
                'status' => 'Pendaftaran ini tidak dalam status antrian MENUNGGU_VERIFIKASI.',
            ]);
        }

        if (trim($catatanPenolakan) === '') {
            throw ValidationException::withMessages([
                'catatan_penolakan' => 'Alasan/catatan penolakan wajib diisi untuk akuntabilitas verifikasi.',
            ]);
        }

        $pendaftaran->update([
            'status'            => 'DITOLAK',
            'catatan_penolakan' => trim($catatanPenolakan),
            'verified_by'       => $verifiedByUserId,
            'verified_at'       => now(),
            'verified_ip'       => $ipAddress ?? request()->ip(),
        ]);

        return $pendaftaran->fresh();
    }
}
