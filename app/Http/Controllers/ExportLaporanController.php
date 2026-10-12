<?php

namespace App\Http\Controllers;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller Pelaporan & Database Cursor Streaming Mass Export.
 * Menghasilkan file CSV/Excel skala besar (100k–1M baris) dengan pemakaian RAM PHP stabil < 20MB.
 * Ref: PRD.md §7.5 (FR-17), ARCHITECTURE.md §7, SECURITY.md §7.3
 */
class ExportLaporanController extends Controller
{
    /**
     * Ekspor Laporan Rekapitulasi Monitoring TTE via Cursor Streaming.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('exportLaporan', PendaftaranTte::class);

        $status = $request->input('status');
        $skpdId = $request->input('skpd_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $filename = 'laporan_monitoring_tte_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($status, $skpdId, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // Tambahkan BOM UTF-8 agar karakter Indonesia dan aksen terbaca sempurna di Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Header Kolom CSV
            fputcsv($handle, [
                'No. Referensi',
                'Nama Lengkap ASN',
                'Unit Kerja (SKPD)',
                'Jabatan',
                'Golongan',
                'Status Memiliki Email',
                'Email Existing',
                'Email Usulan',
                'Email Definitif',
                'Status Pendaftaran',
                'Alasan Penolakan',
                'Tanggal Pendaftaran',
                'Tanggal Terbit Sertifikat',
                'Tanggal Kadaluarsa Sertifikat',
                'Status Sertifikat',
            ]);

            // Database Cursor Streaming Terpadu (Single Query Join: RAM < 20MB & 0 N+1 Queries)
            $query = DB::table('pendaftaran_tte')
                ->leftJoin('skpd', 'pendaftaran_tte.skpd_id', '=', 'skpd.id')
                ->leftJoin('master_jabatan_golongan as jab', 'pendaftaran_tte.jabatan_id', '=', 'jab.id')
                ->leftJoin('master_jabatan_golongan as gol', 'pendaftaran_tte.golongan_id', '=', 'gol.id')
                ->leftJoin('sertifikat_tte', 'pendaftaran_tte.id', '=', 'sertifikat_tte.pendaftaran_id')
                ->select([
                    'pendaftaran_tte.nomor_referensi',
                    'pendaftaran_tte.nama_lengkap',
                    'skpd.nama_skpd',
                    'jab.nama_label as nama_jabatan',
                    'gol.nama_label as nama_golongan',
                    'pendaftaran_tte.status_memiliki_email',
                    'pendaftaran_tte.email_existing',
                    'pendaftaran_tte.email_usulan',
                    'pendaftaran_tte.email_final_terverifikasi',
                    'pendaftaran_tte.status',
                    'pendaftaran_tte.catatan_penolakan',
                    'pendaftaran_tte.created_at',
                    'sertifikat_tte.tanggal_penerbitan',
                    'sertifikat_tte.tanggal_kadaluarsa',
                    'sertifikat_tte.status_sertifikat',
                ])
                ->when($status, fn($q) => $q->where('pendaftaran_tte.status', $status))
                ->when($skpdId, fn($q) => $q->where('pendaftaran_tte.skpd_id', $skpdId))
                ->when($startDate, fn($q) => $q->whereDate('pendaftaran_tte.created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('pendaftaran_tte.created_at', '<=', $endDate))
                ->orderBy('pendaftaran_tte.id', 'asc');

            $query->cursor()->each(function ($row) use ($handle) {
                fputcsv($handle, [
                    $row->nomor_referensi,
                    $row->nama_lengkap,
                    $row->nama_skpd ?? '-',
                    $row->nama_jabatan ?? '-',
                    $row->nama_golongan ?? '-',
                    $row->status_memiliki_email ? 'Sudah Ada' : 'Belum Ada',
                    $row->email_existing ?? '-',
                    $row->email_usulan ?? '-',
                    $row->email_final_terverifikasi ?? '-',
                    $row->status,
                    $row->catatan_penolakan ?? '-',
                    $row->created_at ? substr((string)$row->created_at, 0, 19) : '-',
                    $row->tanggal_penerbitan ?? '-',
                    $row->tanggal_kadaluarsa ?? '-',
                    $row->status_sertifikat ?? '-',
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
