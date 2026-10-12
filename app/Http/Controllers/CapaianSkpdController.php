<?php

namespace App\Http\Controllers;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller Analitik Capaian Adopsi TTE per SKPD — Seluruh Unit Kerja.
 * Menampilkan persentase capaian adopsi TTE terhadap total pegawai ASN
 * dari masing-masing SKPD aktif di lingkungan Pemerintah Kota Cirebon,
 * dilengkapi KPI agregat kota, filter, pencarian, dan fitur ekspor CSV.
 */
class CapaianSkpdController extends Controller
{
    /**
     * Tampilkan halaman analitik capaian adopsi TTE seluruh SKPD.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        $this->authorize('exportLaporan', PendaftaranTte::class);

        $search    = $request->input('q');
        $kategori  = $request->input('kategori');
        $sortMode  = $request->input('sort', 'persentase_desc');
        $allowedSorts = [
            'persentase_desc', 'persentase_asc',
            'volume_desc', 'volume_asc',
            'total_asn_desc', 'nama_skpd_asc',
        ];
        $sortMode = in_array($sortMode, $allowedSorts) ? $sortMode : 'persentase_desc';

        // Query agregasi seluruh SKPD aktif
        $innerQuery = DB::table('skpd')
            ->leftJoin('pendaftaran_tte', 'skpd.id', '=', 'pendaftaran_tte.skpd_id')
            ->select(
                'skpd.id',
                'skpd.uuid',
                'skpd.nama_skpd',
                'skpd.kode_skpd',
                'skpd.total_asn',
                DB::raw("COUNT(pendaftaran_tte.id) as total_pendaftar"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DISETUJUI' THEN 1 END) as total_disetujui"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'MENUNGGU_VERIFIKASI' THEN 1 END) as total_antrian"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DITOLAK' THEN 1 END) as total_ditolak"),
                DB::raw("CASE WHEN COALESCE(skpd.total_asn, 0) > 0 THEN ROUND((COUNT(pendaftaran_tte.id)::numeric / skpd.total_asn) * 100, 1) ELSE 0 END as persentase_capaian")
            )
            ->where('skpd.is_active', true)
            ->groupBy('skpd.id', 'skpd.uuid', 'skpd.nama_skpd', 'skpd.kode_skpd', 'skpd.total_asn');

        // Pencarian nama/kode SKPD
        if ($search) {
            $innerQuery->where(function ($q) use ($search) {
                $q->where('skpd.nama_skpd', 'ilike', "%{$search}%")
                  ->orWhere('skpd.kode_skpd', 'ilike', "%{$search}%");
            });
        }

        // Wrap in subquery to allow filtering on aggregated columns
        $baseQuery = DB::query()->fromSub($innerQuery, 'skpd_agg');

        // Filter kategori kepatuhan
        if ($kategori && $kategori !== 'semua') {
            match ($kategori) {
                'optimal'       => $baseQuery->where('persentase_capaian', '>=', 80),
                'progres'       => $baseQuery->where('persentase_capaian', '>=', 50)->where('persentase_capaian', '<', 80),
                'percepatan'    => $baseQuery->where('persentase_capaian', '>', 0)->where('persentase_capaian', '<', 50),
                'belum_diset'   => $baseQuery->where('total_asn', '<=', 0),
                default         => null,
            };
        }

        // Sorting
        match ($sortMode) {
            'persentase_desc' => $baseQuery->orderByDesc('persentase_capaian'),
            'persentase_asc'  => $baseQuery->orderBy('persentase_capaian'),
            'volume_desc'     => $baseQuery->orderByDesc('total_pendaftar'),
            'volume_asc'      => $baseQuery->orderBy('total_pendaftar'),
            'total_asn_desc'  => $baseQuery->orderByDesc('total_asn'),
            'nama_skpd_asc'   => $baseQuery->orderBy('nama_skpd'),
            default           => $baseQuery->orderByDesc('persentase_capaian'),
        };

        $skpdData = $baseQuery->paginate(20)->withQueryString();

        // KPI Agregat Kota Cirebon (cached 300s)
        $kpiKota = Cache::remember('capaian_skpd_kpi_kota', 300, function () {
            $all = DB::table('skpd')
                ->leftJoin('pendaftaran_tte', 'skpd.id', '=', 'pendaftaran_tte.skpd_id')
                ->select(
                    'skpd.id',
                    'skpd.total_asn',
                    DB::raw("COUNT(pendaftaran_tte.id) as total_pendaftar"),
                    DB::raw("CASE WHEN COALESCE(skpd.total_asn, 0) > 0 THEN ROUND((COUNT(pendaftaran_tte.id)::numeric / skpd.total_asn) * 100, 1) ELSE 0 END as persentase_capaian")
                )
                ->where('skpd.is_active', true)
                ->groupBy('skpd.id', 'skpd.total_asn')
                ->get();

            $totalSkpd       = $all->count();
            $totalAsnKota    = $all->sum('total_asn');
            $totalPemohon    = $all->sum('total_pendaftar');
            $skpdWithTarget  = $all->filter(fn($s) => ($s->total_asn ?? 0) > 0);
            $rataCapaian     = $skpdWithTarget->count() > 0
                ? round($skpdWithTarget->avg('persentase_capaian'), 1)
                : 0;

            $optimal     = $all->filter(fn($s) => ($s->total_asn ?? 0) > 0 && $s->persentase_capaian >= 80)->count();
            $progres     = $all->filter(fn($s) => ($s->total_asn ?? 0) > 0 && $s->persentase_capaian >= 50 && $s->persentase_capaian < 80)->count();
            $percepatan  = $all->filter(fn($s) => ($s->total_asn ?? 0) > 0 && $s->persentase_capaian < 50)->count();
            $belumDiset  = $all->filter(fn($s) => ($s->total_asn ?? 0) <= 0)->count();

            return [
                'total_skpd'      => $totalSkpd,
                'total_asn_kota'  => $totalAsnKota,
                'total_pemohon'   => $totalPemohon,
                'rata_capaian'    => $rataCapaian,
                'optimal'         => $optimal,
                'progres'         => $progres,
                'percepatan'      => $percepatan,
                'belum_diset'     => $belumDiset,
            ];
        });

        $data = [
            'skpdData'  => $skpdData,
            'kpiKota'   => $kpiKota,
            'search'    => $search,
            'kategori'  => $kategori,
            'sortMode'  => $sortMode,
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return view('admin.capaian_skpd.index', $data);
    }

    /**
     * Ekspor data capaian adopsi TTE seluruh SKPD ke CSV (Cursor Streaming).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('exportLaporan', PendaftaranTte::class);

        $search   = $request->input('q');
        $kategori = $request->input('kategori');
        $sortMode = $request->input('sort', 'persentase_desc');

        $filename = 'capaian_adopsi_tte_skpd_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($search, $kategori, $sortMode) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM untuk kompatibilitas Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Header Kolom CSV
            fputcsv($handle, [
                'Peringkat',
                'Kode SKPD',
                'Nama Unit Kerja (SKPD)',
                'Target Pegawai (ASN)',
                'Total Pemohon TTE',
                'Disetujui',
                'Menunggu Verifikasi',
                'Ditolak',
                'Persentase Capaian (%)',
                'Status Kategori',
                'Tanggal Data Diekspor',
            ]);

            // Build query
            $innerQuery = DB::table('skpd')
                ->leftJoin('pendaftaran_tte', 'skpd.id', '=', 'pendaftaran_tte.skpd_id')
                ->select(
                    'skpd.id',
                    'skpd.uuid',
                    'skpd.nama_skpd',
                    'skpd.kode_skpd',
                    'skpd.total_asn',
                    DB::raw("COUNT(pendaftaran_tte.id) as total_pendaftar"),
                    DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DISETUJUI' THEN 1 END) as total_disetujui"),
                    DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'MENUNGGU_VERIFIKASI' THEN 1 END) as total_antrian"),
                    DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DITOLAK' THEN 1 END) as total_ditolak"),
                    DB::raw("CASE WHEN COALESCE(skpd.total_asn, 0) > 0 THEN ROUND((COUNT(pendaftaran_tte.id)::numeric / skpd.total_asn) * 100, 1) ELSE 0 END as persentase_capaian")
                )
                ->where('skpd.is_active', true)
                ->groupBy('skpd.id', 'skpd.uuid', 'skpd.nama_skpd', 'skpd.kode_skpd', 'skpd.total_asn');

            if ($search) {
                $innerQuery->where(function ($q) use ($search) {
                    $q->where('skpd.nama_skpd', 'ilike', "%{$search}%")
                      ->orWhere('skpd.kode_skpd', 'ilike', "%{$search}%");
                });
            }

            $outerQuery = DB::query()->fromSub($innerQuery, 'skpd_agg');

            if ($kategori && $kategori !== 'semua') {
                match ($kategori) {
                    'optimal'       => $outerQuery->where('persentase_capaian', '>=', 80),
                    'progres'       => $outerQuery->where('persentase_capaian', '>=', 50)->where('persentase_capaian', '<', 80),
                    'percepatan'    => $outerQuery->where('persentase_capaian', '>', 0)->where('persentase_capaian', '<', 50),
                    'belum_diset'   => $outerQuery->where('total_asn', '<=', 0),
                    default         => null,
                };
            }

            // Sort
            match ($sortMode) {
                'persentase_asc'  => $outerQuery->orderBy('persentase_capaian'),
                'volume_desc'     => $outerQuery->orderByDesc('total_pendaftar'),
                'volume_asc'      => $outerQuery->orderBy('total_pendaftar'),
                'total_asn_desc'  => $outerQuery->orderByDesc('total_asn'),
                'nama_skpd_asc'   => $outerQuery->orderBy('nama_skpd'),
                default           => $outerQuery->orderByDesc('persentase_capaian'),
            };

            $tanggalEkspor = date('d/m/Y H:i:s') . ' WIB';
            $rank = 0;

            $outerQuery->cursor()->each(function ($row) use ($handle, &$rank, $tanggalEkspor) {
                $rank++;
                $persen = (float) ($row->persentase_capaian ?? 0);
                $totalAsn = (int) ($row->total_asn ?? 0);

                if ($totalAsn <= 0) {
                    $statusKategori = 'Belum Diset Target';
                } elseif ($persen >= 80) {
                    $statusKategori = 'Optimal (>= 80%)';
                } elseif ($persen >= 50) {
                    $statusKategori = 'Progres (50% - 79%)';
                } else {
                    $statusKategori = 'Perlu Percepatan (< 50%)';
                }

                fputcsv($handle, [
                    $rank,
                    $row->kode_skpd,
                    $row->nama_skpd,
                    $totalAsn > 0 ? $totalAsn : 'Belum Diset',
                    $row->total_pendaftar,
                    $row->total_disetujui,
                    $row->total_antrian,
                    $row->total_ditolak,
                    number_format($persen, 1),
                    $statusKategori,
                    $tanggalEkspor,
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    /**
     * Detail breakdown analitik capaian adopsi TTE secara dinamis untuk seluruh SKPD.
     * Menampilkan struktur hierarki jabatan (Struktural, Fungsional, Pelaksana),
     * rincian per jabatan, rincian per golongan/pangkat, dan daftar pemohon terkini.
     */
    public function breakdown(Request $request, string $id): JsonResponse
    {
        $this->authorize('exportLaporan', PendaftaranTte::class);

        $skpd = \Illuminate\Support\Str::isUuid($id)
            ? \App\Domain\Master\Models\Skpd::where('uuid', $id)->firstOrFail()
            : \App\Domain\Master\Models\Skpd::findOrFail(is_numeric($id) ? (int)$id : 0);

        // 1. Metrik Agregat SKPD
        $totalPendaftar = DB::table('pendaftaran_tte')->where('skpd_id', $skpd->id)->count();
        $totalDisetujui = DB::table('pendaftaran_tte')->where('skpd_id', $skpd->id)->where('status', 'DISETUJUI')->count();
        $totalAntrian   = DB::table('pendaftaran_tte')->where('skpd_id', $skpd->id)->where('status', 'MENUNGGU_VERIFIKASI')->count();
        $totalDitolak   = DB::table('pendaftaran_tte')->where('skpd_id', $skpd->id)->where('status', 'DITOLAK')->count();
        $totalAsn       = (int) ($skpd->total_asn ?? 0);
        $persentase     = $totalAsn > 0 ? round(($totalPendaftar / $totalAsn) * 100, 1) : 0;

        // 1.5. Breakdown per Deskripsi Penempatan ASN (Dinamis Seluruh SKPD)
        $subKategoriStats = DB::table('pendaftaran_tte')
            ->where('pendaftaran_tte.skpd_id', $skpd->id)
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(pendaftaran_tte.deskripsi), ''), 'Kantor Induk / Penempatan Umum') as sub_unit_kerja"),
                DB::raw("COUNT(pendaftaran_tte.id) as total_pendaftar"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DISETUJUI' THEN 1 END) as total_disetujui"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'MENUNGGU_VERIFIKASI' THEN 1 END) as total_antrian"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DITOLAK' THEN 1 END) as total_ditolak")
            )
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(pendaftaran_tte.deskripsi), ''), 'Kantor Induk / Penempatan Umum')"))
            ->orderByDesc('total_pendaftar')
            ->get();

        // Ambil target ASN per sub-unit dari master jika telah diset
        $masterSubUnits = DB::table('skpd_sub_units')
            ->where('skpd_id', $skpd->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('nama_sub_unit');

        $breakdownSubKategori = $subKategoriStats->map(function ($item) use ($masterSubUnits, $totalPendaftar) {
            $master = $masterSubUnits->get($item->sub_unit_kerja);
            $target = $master->target_asn ?? null;
            $persen = ($target && $target > 0)
                ? round(($item->total_disetujui / $target) * 100, 1)
                : ($totalPendaftar > 0 ? round(($item->total_pendaftar / $totalPendaftar) * 100, 1) : 0);

            $badgeKategori = ['label' => '📍 ' . $item->sub_unit_kerja, 'class' => 'badge', 'color' => '#0f62fe'];

            return [
                'sub_kategori_asn'   => 'PENEMPATAN',
                'sub_unit_kerja'     => $item->sub_unit_kerja,
                'deskripsi'          => $item->sub_unit_kerja,
                'badge'              => $badgeKategori,
                'target_asn'         => $target,
                'total_pendaftar'    => (int)$item->total_pendaftar,
                'total_disetujui'    => (int)$item->total_disetujui,
                'total_antrian'      => (int)$item->total_antrian,
                'total_ditolak'      => (int)$item->total_ditolak,
                'persentase_capaian' => $persen,
            ];
        });

        // Agregasi Kategori Makro (RSUD, Puskesmas, Dinas Induk, Penugasan Eksternal, dll.)
        $kategoriMakro = [];
        foreach ($breakdownSubKategori as $bs) {
            $kat = $bs['sub_kategori_asn'];
            if (!isset($kategoriMakro[$kat])) {
                $kategoriMakro[$kat] = [
                    'kategori'        => $kat,
                    'badge'           => $bs['badge'],
                    'total_pendaftar' => 0,
                    'total_disetujui' => 0,
                    'total_antrian'   => 0,
                    'total_ditolak'   => 0,
                    'sub_units'       => [],
                ];
            }
            $kategoriMakro[$kat]['total_pendaftar'] += $bs['total_pendaftar'];
            $kategoriMakro[$kat]['total_disetujui'] += $bs['total_disetujui'];
            $kategoriMakro[$kat]['total_antrian']   += $bs['total_antrian'];
            $kategoriMakro[$kat]['total_ditolak']   += $bs['total_ditolak'];
            $kategoriMakro[$kat]['sub_units'][]     = $bs['sub_unit_kerja'];
        }

        // 2. Breakdown per Jabatan Riil
        $breakdownJabatan = DB::table('pendaftaran_tte')
            ->leftJoin('master_jabatan_golongan', 'pendaftaran_tte.jabatan_id', '=', 'master_jabatan_golongan.id')
            ->where('pendaftaran_tte.skpd_id', $skpd->id)
            ->select(
                DB::raw("COALESCE(master_jabatan_golongan.nama_label, 'Belum Ditentukan') as nama_jabatan"),
                DB::raw("COALESCE(master_jabatan_golongan.kode, '-') as kode_jabatan"),
                DB::raw("COUNT(pendaftaran_tte.id) as total"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DISETUJUI' THEN 1 END) as disetujui"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'MENUNGGU_VERIFIKASI' THEN 1 END) as antrian"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DITOLAK' THEN 1 END) as ditolak")
            )
            ->groupBy('master_jabatan_golongan.nama_label', 'master_jabatan_golongan.kode')
            ->orderByDesc('total')
            ->get();

        // 3. Klasifikasi Hierarki Organisasi (Struktural, Fungsional Keahlian, Pelaksana)
        $hierarkiStruktural = ['total' => 0, 'disetujui' => 0, 'antrian' => 0];
        $hierarkiFungsional = ['total' => 0, 'disetujui' => 0, 'antrian' => 0];
        $hierarkiPelaksana  = ['total' => 0, 'disetujui' => 0, 'antrian' => 0];

        foreach ($breakdownJabatan as $jab) {
            $nama = strtolower($jab->nama_jabatan);
            if (str_contains($nama, 'kepala') || str_contains($nama, 'sekretaris') || str_contains($nama, 'camat') || str_contains($nama, 'lurah') || str_contains($nama, 'kasubag') || str_contains($nama, 'kasi')) {
                $hierarkiStruktural['total'] += $jab->total;
                $hierarkiStruktural['disetujui'] += $jab->disetujui;
                $hierarkiStruktural['antrian'] += $jab->antrian;
            } elseif (str_contains($nama, 'analis') || str_contains($nama, 'pranata') || str_contains($nama, 'sandiman') || str_contains($nama, 'auditor') || str_contains($nama, 'medis') || str_contains($nama, 'guru') || str_contains($nama, 'dokter') || str_contains($nama, 'perawat')) {
                $hierarkiFungsional['total'] += $jab->total;
                $hierarkiFungsional['disetujui'] += $jab->disetujui;
                $hierarkiFungsional['antrian'] += $jab->antrian;
            } else {
                $hierarkiPelaksana['total'] += $jab->total;
                $hierarkiPelaksana['disetujui'] += $jab->disetujui;
                $hierarkiPelaksana['antrian'] += $jab->antrian;
            }
        }

        // 4. Breakdown per Golongan (Pangkat ASN)
        $breakdownGolongan = DB::table('pendaftaran_tte')
            ->leftJoin('master_jabatan_golongan', 'pendaftaran_tte.golongan_id', '=', 'master_jabatan_golongan.id')
            ->where('pendaftaran_tte.skpd_id', $skpd->id)
            ->select(
                DB::raw("COALESCE(master_jabatan_golongan.kode, '-') as kode_golongan"),
                DB::raw("COALESCE(master_jabatan_golongan.nama_label, 'Belum Ditentukan') as nama_pangkat"),
                DB::raw("COUNT(pendaftaran_tte.id) as total"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'DISETUJUI' THEN 1 END) as disetujui"),
                DB::raw("COUNT(CASE WHEN pendaftaran_tte.status = 'MENUNGGU_VERIFIKASI' THEN 1 END) as antrian")
            )
            ->groupBy('master_jabatan_golongan.kode', 'master_jabatan_golongan.nama_label')
            ->orderBy('master_jabatan_golongan.kode', 'desc')
            ->get();

        // 5. Agregasi Tingkat Golongan Utama (IV, III, II, I)
        $golonganTingkat = [
            'Golongan IV (Pembina)' => 0,
            'Golongan III (Penata)' => 0,
            'Golongan II (Pengatur)' => 0,
            'Golongan I (Juru)' => 0,
            'Lainnya' => 0,
        ];
        foreach ($breakdownGolongan as $gol) {
            $kode = strtoupper($gol->kode_golongan);
            if (str_starts_with($kode, 'IV')) {
                $golonganTingkat['Golongan IV (Pembina)'] += $gol->total;
            } elseif (str_starts_with($kode, 'III')) {
                $golonganTingkat['Golongan III (Penata)'] += $gol->total;
            } elseif (str_starts_with($kode, 'II')) {
                $golonganTingkat['Golongan II (Pengatur)'] += $gol->total;
            } elseif (str_starts_with($kode, 'I/')) {
                $golonganTingkat['Golongan I (Juru)'] += $gol->total;
            } else {
                $golonganTingkat['Lainnya'] += $gol->total;
            }
        }

        // 6. Filter Deskripsi Penempatan / Unit untuk Daftar Pemohon jika ada
        $filterSubUnit = $request->input('sub_unit') ?: $request->input('deskripsi');

        // 7. Daftar Pemohon Terkini di SKPD Ini
        $pemohonTerbaru = PendaftaranTte::with(['jabatan', 'golongan', 'sertifikat'])
            ->where('skpd_id', $skpd->id)
            ->when($filterSubUnit, fn($q) => $q->where('deskripsi', 'ilike', "%{$filterSubUnit}%"))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                $rawNip = $item->nip ?? '';
                $maskedNip = strlen($rawNip) >= 8
                    ? substr($rawNip, 0, 8) . '********' . substr($rawNip, -2)
                    : $rawNip;

                return [
                    'nomor_referensi'   => $item->nomor_referensi,
                    'nama_lengkap'      => $item->nama_lengkap,
                    'nip'               => $maskedNip,
                    'deskripsi'         => $item->deskripsi ?? 'Kantor Induk',
                    'sub_kategori_asn'  => 'PENEMPATAN',
                    'sub_unit_kerja'    => $item->deskripsi ?? 'Kantor Induk',
                    'jabatan'           => $item->jabatan->nama_label ?? '-',
                    'golongan'          => $item->golongan->kode ?? '-',
                    'status'            => $item->status,
                    'status_sertifikat' => $item->sertifikat->status_sertifikat ?? null,
                    'email_dinas'       => $item->email_final_terverifikasi ?? $item->email_usulan ?? $item->email_existing,
                    'tanggal_daftar'    => $item->created_at?->format('d/m/Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'skpd' => [
                'id'                 => $skpd->id,
                'uuid'               => $skpd->uuid,
                'kode_skpd'          => $skpd->kode_skpd,
                'nama_skpd'          => $skpd->nama_skpd,
                'total_asn'          => $totalAsn,
                'total_pendaftar'    => $totalPendaftar,
                'total_disetujui'    => $totalDisetujui,
                'total_antrian'      => $totalAntrian,
                'total_ditolak'      => $totalDitolak,
                'persentase_capaian' => $persentase,
            ],
            'hierarki' => [
                'struktural' => $hierarkiStruktural,
                'fungsional' => $hierarkiFungsional,
                'pelaksana'  => $hierarkiPelaksana,
            ],
            'breakdown_sub_kategori' => $breakdownSubKategori,
            'kategori_makro'         => array_values($kategoriMakro),
            'breakdown_jabatan'      => $breakdownJabatan,
            'breakdown_golongan'     => $breakdownGolongan,
            'golongan_tingkat'       => $golonganTingkat,
            'pemohon_terbaru'        => $pemohonTerbaru,
        ]);
    }

    /**
     * Ekspor detail breakdown pendaftar TTE untuk satu SKPD ke CSV.
     */
    public function exportBreakdownCsv(Request $request, string $id): StreamedResponse
    {
        $this->authorize('exportLaporan', PendaftaranTte::class);

        $skpd = \Illuminate\Support\Str::isUuid($id)
            ? \App\Domain\Master\Models\Skpd::where('uuid', $id)->firstOrFail()
            : \App\Domain\Master\Models\Skpd::findOrFail(is_numeric($id) ? (int)$id : 0);

        $filterDeskripsi = $request->input('sub_unit') ?: $request->input('deskripsi');

        $filename = 'breakdown_tte_' . strtolower($skpd->kode_skpd) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($skpd, $filterDeskripsi) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No',
                'Nomor Referensi',
                'Nama ASN',
                'NIP',
                'Unit Kerja (SKPD)',
                'Sub-Kategori ASN',
                'Sub-Unit / Unit Penempatan',
                'Deskripsi Penempatan',
                'Jabatan',
                'Golongan',
                'Status Permohonan',
                'Email Dinas',
                'Status Sertifikat',
                'Tanggal Pendaftaran',
            ]);

            $query = DB::table('pendaftaran_tte')
                ->leftJoin('master_jabatan_golongan as jab', 'pendaftaran_tte.jabatan_id', '=', 'jab.id')
                ->leftJoin('master_jabatan_golongan as gol', 'pendaftaran_tte.golongan_id', '=', 'gol.id')
                ->leftJoin('sertifikat_tte as cert', 'pendaftaran_tte.id', '=', 'cert.pendaftaran_id')
                ->where('pendaftaran_tte.skpd_id', $skpd->id)
                ->when($filterDeskripsi, fn($q) => $q->where('pendaftaran_tte.deskripsi', 'ilike', "%{$filterDeskripsi}%"))
                ->select(
                    'pendaftaran_tte.nomor_referensi',
                    'pendaftaran_tte.nama_lengkap',
                    'pendaftaran_tte.nip',
                    'pendaftaran_tte.deskripsi',
                    'pendaftaran_tte.status',
                    'pendaftaran_tte.email_final_terverifikasi',
                    'pendaftaran_tte.email_usulan',
                    'pendaftaran_tte.email_existing',
                    'pendaftaran_tte.created_at',
                    'jab.nama_label as jabatan',
                    'gol.kode as golongan',
                    'cert.status_sertifikat'
                )
                ->orderBy('pendaftaran_tte.created_at', 'desc');

            $no = 0;
            $query->cursor()->each(function ($row) use ($handle, &$no, $skpd) {
                $no++;
                fputcsv($handle, [
                    $no,
                    $row->nomor_referensi,
                    $row->nama_lengkap,
                    \App\Services\Security\CryptoHelper::decryptField($row->nip),
                    $skpd->nama_skpd,
                    'PENEMPATAN',
                    $row->deskripsi ?? 'Kantor Induk',
                    $row->deskripsi ?? 'Kantor Induk',
                    $row->jabatan ?? '-',
                    $row->golongan ?? '-',
                    $row->status,
                    $row->email_final_terverifikasi ?? $row->email_usulan ?? $row->email_existing ?? '-',
                    $row->status_sertifikat ?? '-',
                    \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i'),
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }
}
