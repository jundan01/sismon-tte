<?php

namespace App\Domain\Pendaftaran\Actions;

use App\Domain\Pendaftaran\Models\PendaftaranTte;

/**
 * Action Update & Penghapusan (Pengosongan) Jabatan dan Golongan ASN oleh Superadmin.
 * Mendukung pemilihan master jabatan/golongan baru maupun pengosongan (set null).
 * Ref: PRD.md §7.2, ARCHITECTURE.md §6.2
 */
class UpdateJabatanGolonganAction
{
    /**
     * Eksekusi update jabatan dan golongan.
     *
     * @param PendaftaranTte $pendaftaran
     * @param int|null $jabatanId
     * @param int|null $golonganId
     * @return PendaftaranTte
     */
    public function execute(PendaftaranTte $pendaftaran, ?int $jabatanId, ?int $golonganId): PendaftaranTte
    {
        $pendaftaran->update([
            'jabatan_id'  => $jabatanId ?: null,
            'golongan_id' => $golonganId ?: null,
        ]);

        return $pendaftaran->fresh(['skpd', 'jabatan', 'golongan']);
    }
}
