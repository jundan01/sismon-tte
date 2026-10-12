<?php

namespace App\Http\Controllers;

use App\Domain\Master\Models\MasterJabatanGolongan;
use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Actions\StorePendaftaranAction;
use App\Http\Requests\StorePendaftaranRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Form Pendaftaran Mandiri TTE Publik.
 * Ref: PRD.md §7.1, ARCHITECTURE.md §6.1, DESIGN.md §5.1
 */
class PendaftaranController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran mandiri atau kembalikan data inisialisasi JSON.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        $skpdList = Skpd::active()
            ->orderBy('nama_skpd')
            ->get(['id', 'kode_skpd', 'nama_skpd']);

        $jabatanList = MasterJabatanGolongan::jabatan()
            ->orderBy('nama_label')
            ->get(['id', 'kode', 'nama_label']);

        $golonganList = MasterJabatanGolongan::golongan()
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama_label']);

        $subUnitList = \App\Domain\Master\Models\SkpdSubUnit::where('is_active', true)
            ->orderBy('nama_sub_unit')
            ->get(['id', 'skpd_id', 'nama_sub_unit', 'kategori_penempatan']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success'        => true,
                'skpd_list'      => $skpdList,
                'sub_unit_list'  => $subUnitList,
                'jabatan_list'   => $jabatanList,
                'golongan_list'  => $golonganList,
            ]);
        }

        return view('public.pendaftaran', [
            'skpdList'     => $skpdList,
            'subUnitList'  => $subUnitList,
            'jabatanList'  => $jabatanList,
            'golonganList' => $golonganList,
        ]);
    }

    /**
     * Proses kirim data pendaftaran mandiri baru.
     */
    public function store(StorePendaftaranRequest $request, StorePendaftaranAction $action): JsonResponse|Response
    {
        $pendaftaran = $action->execute(
            $request->validated()
        );

        $responseMessage = 'Pendaftaran TTE berhasil dikirim. Data Anda sedang dalam proses verifikasi oleh Tim Persandian Diskominfo Kota Cirebon.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success'         => true,
                'message'         => $responseMessage,
                'nomor_referensi' => $pendaftaran->nomor_referensi,
                'status'          => $pendaftaran->status,
                'email_usulan'    => $pendaftaran->email_usulan,
            ], 201);
        }

        return redirect()->route('home')->with('success', $responseMessage);
    }
}
