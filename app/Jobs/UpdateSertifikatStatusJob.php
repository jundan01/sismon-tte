<?php

namespace App\Jobs;

use App\Domain\Sertifikat\Models\SertifikatTte;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Daily Task Scanner (00:01 WIB) Pemantau Siklus Hidup Sertifikat TTE.
 * Memindai masa berlaku, mengubah status menjadi WARNING_H3 atau EXPIRED,
 * dan memicu pembaruan agregator statistik harian.
 * Ref: PRD.md §7.4 (FR-12), ARCHITECTURE.md §10
 */
class UpdateSertifikatStatusJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $today = Carbon::today();
        $warningThreshold = $today->copy()->addDays(7);

        Log::info('UpdateSertifikatStatusJob: Memulai pemindaian siklus hidup sertifikat TTE...');

        // 1. Perbarui sertifikat yang sudah melewati tanggal kadaluarsa menjadi EXPIRED
        $expiredCount = SertifikatTte::whereNotIn('status_sertifikat', ['EXPIRED', 'REVOKED'])
            ->where('tanggal_kadaluarsa', '<', $today->toDateString())
            ->update([
                'status_sertifikat' => 'EXPIRED',
            ]);

        // 2. Perbarui sertifikat yang memasuki masa H-7 menjadi WARNING_H7
        $warningCount = SertifikatTte::where('status_sertifikat', 'AKTIF')
            ->where('tanggal_kadaluarsa', '>=', $today->toDateString())
            ->where('tanggal_kadaluarsa', '<=', $warningThreshold->toDateString())
            ->update([
                'status_sertifikat' => 'WARNING_H7',
            ]);

        Log::info("UpdateSertifikatStatusJob: Selesai. {$expiredCount} sertifikat kedaluwarsa (EXPIRED), {$warningCount} sertifikat dalam peringatan (WARNING_H7).");

        // 3. Picu pembaruan agregator fast counter dashboard
        RefreshDailyAggregatorJob::dispatch();
    }
}
