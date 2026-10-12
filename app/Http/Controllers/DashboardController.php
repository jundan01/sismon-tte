<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RefreshDailyAggregatorAction;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Dashboard Superadmin & Fast Analytics.
 * Menyuplai data indikator kunci sub-milidetik dari Redis Cache & fast aggregator.
 * Ref: PRD.md §7.4 (FR-13), ARCHITECTURE.md §6.4, DESIGN.md §7
 */
class DashboardController extends Controller
{
    public function __construct(
        protected RefreshDailyAggregatorAction $aggregatorAction
    ) {}

    /**
     * Tampilkan data ringkasan dashboard Superadmin.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        // Ambil metrik dari Redis Cache (kecepatan sub-milidetik < 50ms)
        $summary = Cache::get(RefreshDailyAggregatorAction::CACHE_KEY);
        if (!$summary) {
            $stats = $this->aggregatorAction->execute();
            $summary = [
                'stat_date'        => $stats->stat_date->toDateString(),
                'total_asn'        => $stats->total_asn,
                'total_terbit'     => $stats->total_terbit,
                'total_antrian'    => $stats->total_antrian,
                'total_warning_h7' => $stats->total_warning_h7 ?? $stats->total_warning_h3,
                'total_warning_h3' => $stats->total_warning_h7 ?? $stats->total_warning_h3,
                'last_updated_at'  => now()->toIso8601String(),
            ];
        }

        // Antrian pendaftaran terbaru (Verifikasi Pertama / FIFO) dengan filter pencarian deskripsi / identitas
        $searchQuery = $request->input('q') ?: $request->input('deskripsi');

        $recentAntrianQuery = PendaftaranTte::with(['skpd', 'jabatan'])
            ->where('status', 'MENUNGGU_VERIFIKASI');

        if ($searchQuery) {
            $trimmed = trim($searchQuery);
            $recentAntrianQuery->where(function ($q) use ($trimmed) {
                $q->where('deskripsi', 'ilike', "%{$trimmed}%")
                  ->orWhere('nama_lengkap', 'ilike', "%{$trimmed}%")
                  ->orWhere('nomor_referensi', 'ilike', "%{$trimmed}%");

                if (ctype_digit($trimmed)) {
                    $encryptedInput = \App\Services\Security\CryptoHelper::encryptField($trimmed);
                    $q->orWhere('nik', $encryptedInput)
                      ->orWhere('nip', $encryptedInput);
                }
            });
        }

        $recentAntrian = $recentAntrianQuery
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        // Sertifikat dalam zona peringatan H-7
        $warningCertificates = SertifikatTte::with(['pendaftaran.skpd'])
            ->whereIn('status_sertifikat', ['WARNING_H7', 'WARNING_H3'])
            ->orderBy('tanggal_kadaluarsa', 'asc')
            ->limit(5)
            ->get();

        // Mode pengurutan distribusi SKPD: 'persentase' (default) atau 'volume'
        $sortMode = $request->input('sort', 'persentase');
        $sortMode = in_array($sortMode, ['persentase', 'volume']) ? $sortMode : 'persentase';

        // Distribusi statistik per SKPD (Cached 300s per mode sorting)
        $cacheKey = 'dashboard_skpd_distribution_stats_' . $sortMode;
        $skpdStats = Cache::remember($cacheKey, 300, function () use ($sortMode) {
            $query = DB::table('skpd')
                ->leftJoin('pendaftaran_tte', 'skpd.id', '=', 'pendaftaran_tte.skpd_id')
                ->select(
                    'skpd.id',
                    'skpd.nama_skpd',
                    'skpd.kode_skpd',
                    'skpd.total_asn',
                    DB::raw("COUNT(pendaftaran_tte.id) as total_pendaftar"),
                    DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DISETUJUI' THEN 1 END) as total_disetujui"),
                    DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'MENUNGGU_VERIFIKASI' THEN 1 END) as total_antrian"),
                    DB::raw("CASE WHEN COALESCE(skpd.total_asn, 0) > 0 THEN ROUND((COUNT(pendaftaran_tte.id)::numeric / skpd.total_asn) * 100, 1) ELSE 0 END as persentase_capaian")
                )
                ->where('skpd.is_active', true)
                ->groupBy('skpd.id', 'skpd.nama_skpd', 'skpd.kode_skpd', 'skpd.total_asn');

            if ($sortMode === 'persentase') {
                $query->orderByDesc('persentase_capaian');
            } else {
                $query->orderByDesc('total_pendaftar');
            }

            return $query->limit(10)->get();
        });

        $data = [
            'summary'             => $summary,
            'recentAntrian'       => $recentAntrian,
            'recent_antrian'      => $recentAntrian,
            'warningCertificates' => $warningCertificates,
            'warning_certificates'=> $warningCertificates,
            'skpdStats'           => $skpdStats,
            'skpd_stats'          => $skpdStats,
            'sortMode'            => $sortMode,
            'sort_mode'           => $sortMode,
            'searchQuery'         => $searchQuery,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return view('admin.dashboard', $data);
    }

    /**
     * Endpoint untuk merefresh fast cache agregator secara manual.
     */
    public function refreshStats(): JsonResponse
    {
        $stats = $this->aggregatorAction->execute();

        return response()->json([
            'success' => true,
            'message' => 'Statistik dashboard berhasil diperbarui.',
            'data'    => $stats,
        ]);
    }
}
