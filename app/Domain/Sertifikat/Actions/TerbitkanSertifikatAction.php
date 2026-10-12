<?php

namespace App\Domain\Sertifikat\Actions;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action Penerbitan Sertifikat TTE Tahap 2.
 * Menghitung masa berlaku 2 tahun dan membuat record sertifikat aktif.
 * Ref: PRD.md §7.3, SCHEMA.md §7.6 & §11.1
 */
class TerbitkanSertifikatAction
{
    /**
     * Eksekusi penerbitan sertifikat TTE.
     */
    public function execute(PendaftaranTte $pendaftaran, string $tanggalPenerbitan, int $verifiedByUserId, ?string $ipAddress = null): SertifikatTte
    {
        if ($pendaftaran->status !== 'DISETUJUI') {
            throw ValidationException::withMessages([
                'status' => 'Sertifikat hanya dapat diterbitkan untuk pendaftaran yang telah DISETUJUI pada Verifikasi Tahap 1.',
            ]);
        }

        if ($pendaftaran->sertifikat()->exists()) {
            throw ValidationException::withMessages([
                'sertifikat' => 'Sertifikat TTE untuk pendaftaran ini sudah pernah diterbitkan sebelumnya.',
            ]);
        }

        $emailDinas = $pendaftaran->email_final_terverifikasi ?? $pendaftaran->email_existing;
        if (empty($emailDinas)) {
            throw ValidationException::withMessages([
                'email_dinas' => 'Alamat email dinas definitif belum ditetapkan. Harap lakukan rekap email terlebih dahulu.',
            ]);
        }

        return DB::transaction(function () use ($pendaftaran, $tanggalPenerbitan, $emailDinas, $verifiedByUserId, $ipAddress) {
            $tglTerbit = Carbon::parse($tanggalPenerbitan);
            // Kalkulasi otomatis masa berlaku +2 tahun sesuai SCHEMA.md §11.1
            $tglKadaluarsa = $tglTerbit->copy()->addYears(2);

            return SertifikatTte::create([
                'pendaftaran_id'     => $pendaftaran->id,
                'email_dinas'        => strtolower(trim($emailDinas)),
                'tanggal_penerbitan' => $tglTerbit->toDateString(),
                'tanggal_kadaluarsa' => $tglKadaluarsa->toDateString(),
                'status_sertifikat'  => 'AKTIF',
                'verified_by_tahap2' => $verifiedByUserId,
                'verified_at_tahap2' => now(),
                'verified_ip_tahap2' => $ipAddress ?? request()->ip(),
            ]);
        });
    }
}
