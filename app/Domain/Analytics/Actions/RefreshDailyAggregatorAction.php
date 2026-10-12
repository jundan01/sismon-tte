<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\DashboardDailyStats;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Action Fast Aggregator Statistik Dashboard Harian.
 * Mengkalkulasi metrik transaksi dan menyimpan ke tabel dashboard_daily_stats serta Redis Cache.
 * Ref: PRD.md §7.4 (FR-13), ARCHITECTURE.md §6.4, SCHEMA.md §7.7
 */
class RefreshDailyAggregatorAction
{
    public const CACHE_KEY = 'dashboard_stats_summary';
    public const CACHE_TTL_SECONDS = 3600; // 1 Jam

    /**
     * Eksekusi kalkulasi statistik dan pembaruan cache.
     */
    public function execute(?string $date = null): DashboardDailyStats
    {
        $statDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $totalAsn = PendaftaranTte::count();
        $totalTerbit = SertifikatTte::where('status_sertifikat', 'AKTIF')->count();
        $totalAntrian = PendaftaranTte::where('status', 'MENUNGGU_VERIFIKASI')->count();
        $totalWarningH7 = SertifikatTte::whereIn('status_sertifikat', ['WARNING_H7', 'WARNING_H3'])->count();

        /** @var DashboardDailyStats $stats */
        $stats = DashboardDailyStats::updateOrCreate(
            ['stat_date' => $statDate],
            [
                'total_asn'        => $totalAsn,
                'total_terbit'     => $totalTerbit,
                'total_antrian'    => $totalAntrian,
                'total_warning_h3' => $totalWarningH7,
                'total_warning_h7' => $totalWarningH7,
                'updated_at'       => now(),
            ]
        );

        // Perbarui Redis Cache untuk performa sub-milidetik Dashboard
        Cache::put(self::CACHE_KEY, [
            'stat_date'        => $stats->stat_date->toDateString(),
            'total_asn'        => $stats->total_asn,
            'total_terbit'     => $stats->total_terbit,
            'total_antrian'    => $stats->total_antrian,
            'total_warning_h3' => $totalWarningH7,
            'total_warning_h7' => $totalWarningH7,
            'last_updated_at'  => now()->toIso8601String(),
        ], self::CACHE_TTL_SECONDS);

        return $stats;
    }
}
