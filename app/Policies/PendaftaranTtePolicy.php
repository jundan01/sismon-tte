<?php

namespace App\Policies;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Models\User;

/**
 * Policy Otorisasi Eksplisit Superadmin pada Modul Pendaftaran TTE.
 * Ref: SECURITY.md §6.1, ARCHITECTURE.md §2
 */
class PendaftaranTtePolicy
{
    /**
     * Izinkan Superadmin melihat daftar antrian pendaftaran.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Izinkan Superadmin melihat detail pendaftaran.
     */
    public function view(User $user, PendaftaranTte $pendaftaran): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Otorisasi Verifikasi & Persetujuan Tahap 1.
     */
    public function approveTahap1(User $user, PendaftaranTte $pendaftaran): bool
    {
        return $user->isSuperadmin() && $pendaftaran->status === 'MENUNGGU_VERIFIKASI';
    }

    /**
     * Otorisasi Penolakan Pendaftaran Tahap 1.
     */
    public function rejectTahap1(User $user, PendaftaranTte $pendaftaran): bool
    {
        return $user->isSuperadmin() && $pendaftaran->status === 'MENUNGGU_VERIFIKASI';
    }

    /**
     * Otorisasi Penetapan & Rekap Email Dinas Definitif.
     */
    public function rekapEmailDinas(User $user, PendaftaranTte $pendaftaran): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Otorisasi Pembaruan / Pengosongan Jabatan & Golongan ASN oleh Superadmin.
     */
    public function updateJabatanGolongan(User $user, PendaftaranTte $pendaftaran): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Otorisasi Ekspor Data Massal.
     */
    public function exportLaporan(User $user): bool
    {
        return $user->isSuperadmin();
    }
}
