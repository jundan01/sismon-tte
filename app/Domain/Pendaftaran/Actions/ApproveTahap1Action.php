<?php

namespace App\Domain\Pendaftaran\Actions;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Services\EmailGeneratorService;
use Illuminate\Validation\ValidationException;

/**
 * Action Verifikasi & Persetujuan Berkas Tahap 1.
 * Mengubah status menjadi DISETUJUI, memvalidasi unisitas email definitif, dan mencatat jejak verifikator.
 * Ref: PRD.md §7.2, ARCHITECTURE.md §6.2
 */
class ApproveTahap1Action
{
    public function __construct(
        protected EmailGeneratorService $emailService
    ) {}

    /**
     * Eksekusi persetujuan Tahap 1.
     */
    public function execute(PendaftaranTte $pendaftaran, int $verifiedByUserId, ?string $ipAddress = null): PendaftaranTte
    {
        if ($pendaftaran->status !== 'MENUNGGU_VERIFIKASI') {
            throw ValidationException::withMessages([
                'status' => 'Pendaftaran ini tidak dalam status antrian MENUNGGU_VERIFIKASI.',
            ]);
        }

        // Pastikan email definitif telah terisi (baik dari rekap manual admin, email existing, atau email usulan)
        if (empty($pendaftaran->email_final_terverifikasi)) {
            $pendaftaran->email_final_terverifikasi = $pendaftaran->status_memiliki_email
                ? $pendaftaran->email_existing
                : $pendaftaran->email_usulan;
            $pendaftaran->is_email_recap_confirmed = true;
        }

        $emailFinal = strtolower(trim((string)$pendaftaran->email_final_terverifikasi));

        // Validasi Unisitas Email sebelum Persetujuan Tahap 1
        if ($this->emailService->isEmailTaken($emailFinal, $pendaftaran->id)) {
            $collision = $this->emailService->checkCollisionDetail($emailFinal, $pendaftaran->id);
            throw ValidationException::withMessages([
                'email_final_terverifikasi' => 'Persetujuan ditolak: ' . ($collision['message'] ?? 'Email dinas terdeteksi duplikat dengan pendaftar/sertifikat lain di basis data.'),
            ]);
        }

        $pendaftaran->update([
            'status'                    => 'DISETUJUI',
            'verified_by'               => $verifiedByUserId,
            'verified_at'               => now(),
            'verified_ip'               => $ipAddress ?? request()->ip(),
            'email_final_terverifikasi' => $emailFinal,
            'is_email_recap_confirmed'  => $pendaftaran->is_email_recap_confirmed,
        ]);

        return $pendaftaran->fresh();
    }
}
