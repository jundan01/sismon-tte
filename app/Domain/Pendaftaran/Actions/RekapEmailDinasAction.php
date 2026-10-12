<?php

namespace App\Domain\Pendaftaran\Actions;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Services\EmailGeneratorService;
use Illuminate\Validation\ValidationException;

/**
 * Action Penetapan & Rekap Email Dinas Definitif oleh Superadmin.
 * Mengunci penetapan alamat email dan memastikan tidak terjadi duplikasi di seluruh basis data.
 * Ref: PRD.md §7.2, ARCHITECTURE.md §6.2
 */
class RekapEmailDinasAction
{
    public function __construct(
        protected EmailGeneratorService $emailService
    ) {}

    /**
     * Eksekusi rekap email dinas.
     */
    public function execute(PendaftaranTte $pendaftaran, string $emailFinal): PendaftaranTte
    {
        $emailClean = strtolower(trim($emailFinal));

        // Validasi unisitas sebelum menyimpan rekap
        if ($this->emailService->isEmailTaken($emailClean, $pendaftaran->id)) {
            $collision = $this->emailService->checkCollisionDetail($emailClean, $pendaftaran->id);
            throw ValidationException::withMessages([
                'email_final_terverifikasi' => $collision['message'] ?? 'Alamat email ini sudah terdaftar atas nama ASN lain di basis data.',
            ]);
        }

        $pendaftaran->update([
            'email_final_terverifikasi' => $emailClean,
            'is_email_recap_confirmed'  => true,
        ]);

        return $pendaftaran->fresh();
    }
}
