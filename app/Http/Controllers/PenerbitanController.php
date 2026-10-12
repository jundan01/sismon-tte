<?php

namespace App\Http\Controllers;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Actions\TerbitkanSertifikatAction;
use App\Domain\Sertifikat\Actions\UpdateStatusSertifikatAction;
use App\Domain\Sertifikat\Models\SertifikatTte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Verifikasi Tahap 2 & Manajemen Sertifikat TTE.
 * Ref: PRD.md §7.3 & §7.4, ARCHITECTURE.md §6.3
 */
class PenerbitanController extends Controller
{
    /**
     * Tampilkan antrian penerbitan sertifikat Tahap 2 & daftar sertifikat aktif.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        $this->authorize('viewAny', SertifikatTte::class);

        $statusFilter = $request->input('status'); // Filter status sertifikat
        $skpdId = $request->input('skpd_id');

        // 1. Antrian Pendaftaran yang sudah DISETUJUI tapi belum diterbitkan sertifikatnya
        $antrianTahap2 = PendaftaranTte::with(['skpd', 'jabatan', 'golongan'])
            ->where('status', 'DISETUJUI')
            ->doesntHave('sertifikat')
            ->orderBy('verified_at', 'asc')
            ->get();

        // 2. Daftar Sertifikat TTE yang telah diterbitkan
        $sertifikatList = SertifikatTte::with(['pendaftaran.skpd', 'pendaftaran.jabatan', 'verifikatorTahap2'])
            ->when($statusFilter, function ($q, $status) {
                if ($status === 'WARNING_H7' || $status === 'WARNING_H3') {
                    $q->whereIn('status_sertifikat', ['WARNING_H7', 'WARNING_H3']);
                } else {
                    $q->where('status_sertifikat', $status);
                }
            })
            ->when($skpdId, function ($q, $skpdId) {
                $q->whereHas('pendaftaran', fn($sub) => $sub->where('skpd_id', $skpdId));
            })
            ->orderBy('tanggal_kadaluarsa', 'asc')
            ->paginate(15)
            ->withQueryString();

        $skpdList = Skpd::active()->orderBy('nama_skpd')->get(['id', 'kode_skpd', 'nama_skpd']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success'         => true,
                'antrian_tahap2'  => $antrianTahap2,
                'sertifikat_list' => $sertifikatList,
                'skpd_list'       => $skpdList,
            ]);
        }

        return view('admin.penerbitan.index', [
            'antrianTahap2'  => $antrianTahap2,
            'sertifikatList' => $sertifikatList,
            'skpdList'       => $skpdList,
            'statusFilter'   => $statusFilter,
        ]);
    }

    /**
     * Proses Penerbitan Sertifikat TTE Tahap 2.
     * Mendukung nomor_referensi resmi maupun ID/UUID.
     */
    public function store(Request $request, string|int $pendaftaranId, TerbitkanSertifikatAction $action): JsonResponse|Response
    {
        $this->authorize('terbitkanSertifikat', SertifikatTte::class);

        $request->validate([
            'tanggal_penerbitan' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'tanggal_penerbitan.required'        => 'Tanggal penerbitan sertifikat wajib diisi.',
            'tanggal_penerbitan.date'            => 'Format tanggal penerbitan tidak valid.',
            'tanggal_penerbitan.before_or_equal' => 'Tanggal penerbitan tidak boleh melebihi hari ini.',
        ]);

        $pendaftaran = \Illuminate\Support\Str::isUuid($pendaftaranId)
            ? PendaftaranTte::where('uuid', $pendaftaranId)->firstOrFail()
            : PendaftaranTte::where('nomor_referensi', $pendaftaranId)
                ->orWhere('id', is_numeric($pendaftaranId) ? (int)$pendaftaranId : 0)
                ->firstOrFail();

        $sertifikat = $action->execute(
            $pendaftaran,
            $request->input('tanggal_penerbitan'),
            auth()->id(),
            $request->ip()
        );

        $message = 'Sertifikat TTE berhasil diterbitkan. Masa berlaku aktif selama 2 tahun hingga ' . $sertifikat->tanggal_kadaluarsa->format('d/m/Y') . '.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success'    => true,
                'message'    => $message,
                'sertifikat' => $sertifikat,
            ], 201);
        }

        return redirect()->route('admin.penerbitan.index')->with('success', $message);
    }

    /**
     * Override / Update Status Sertifikat (REVOKED, PERLU_PERPANJANG, WARNING_H7, dll).
     */
    public function updateStatus(Request $request, string|int $sertifikatId, UpdateStatusSertifikatAction $action): JsonResponse
    {
        $request->validate([
            'status_sertifikat' => ['required', 'string', 'in:AKTIF,WARNING_H7,WARNING_H3,EXPIRED,REVOKED,PERLU_PERPANJANG'],
        ]);

        $sertifikat = \Illuminate\Support\Str::isUuid($sertifikatId)
            ? SertifikatTte::where('uuid', $sertifikatId)->firstOrFail()
            : SertifikatTte::where('id', is_numeric($sertifikatId) ? (int)$sertifikatId : 0)->firstOrFail();
        
        $this->authorize('updateStatus', $sertifikat);

        $updated = $action->execute($sertifikat, $request->input('status_sertifikat'));

        return response()->json([
            'success'    => true,
            'message'    => 'Status sertifikat berhasil diperbarui menjadi ' . $updated->status_sertifikat . '.',
            'sertifikat' => $updated,
        ]);
    }
}
