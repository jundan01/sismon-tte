<?php

namespace App\Domain\Sertifikat\Actions;

use App\Domain\Sertifikat\Models\SertifikatTte;
use Illuminate\Validation\ValidationException;

/**
 * Action Pembaruan & Override Status Siklus Hidup Sertifikat TTE.
 * Ref: PRD.md §7.4, DESIGN.md §3, SCHEMA.md §7.6
 */
class UpdateStatusSertifikatAction
{
    public const VALID_STATUSES = ['AKTIF', 'WARNING_H7', 'WARNING_H3', 'EXPIRED', 'REVOKED', 'PERLU_PERPANJANG'];

    /**
     * Eksekusi update status sertifikat.
     */
    public function execute(SertifikatTte $sertifikat, string $statusBaru): SertifikatTte
    {
        $statusBaru = strtoupper(trim($statusBaru));

        if (!in_array($statusBaru, self::VALID_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status_sertifikat' => 'Status sertifikat tidak valid. Pilihan status: ' . implode(', ', self::VALID_STATUSES),
            ]);
        }

        $sertifikat->update([
            'status_sertifikat' => $statusBaru,
        ]);

        return $sertifikat->fresh();
    }
}
