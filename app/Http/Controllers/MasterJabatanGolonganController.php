<?php

namespace App\Http\Controllers;

use App\Domain\Master\Models\MasterJabatanGolongan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Manajemen Master Data Jabatan & Golongan ASN.
 * Ref: PRD.md FR-19, SCHEMA.md §7.2, ARCHITECTURE.md §6.5
 */
class MasterJabatanGolonganController extends Controller
{
    /**
     * Tampilkan daftar master jabatan & golongan.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        $kategori = $request->input('kategori'); // 'JABATAN' atau 'GOLONGAN'
        $search = $request->input('q');

        $list = MasterJabatanGolongan::when($kategori, fn($q) => $q->where('kategori', strtoupper($kategori)))
            ->when($search, function ($q, $search) {
                $q->where('nama_label', 'ilike', "%{$search}%")
                  ->orWhere('kode', 'ilike', "%{$search}%");
            })
            ->orderBy('kategori', 'asc')
            ->orderBy('kode', 'asc')
            ->paginate(25)
            ->withQueryString();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $list,
            ]);
        }

        return view('admin.master.jabatan_golongan.index', [
            'list'            => $list,
            'currentKategori' => $kategori,
        ]);
    }

    /**
     * Tambah master Jabatan / Golongan baru.
     */
    public function store(Request $request): JsonResponse|Response
    {
        $validated = $request->validate([
            'kategori'   => ['required', 'string', 'in:JABATAN,GOLONGAN'],
            'kode'       => ['required', 'string', 'max:50', 'unique:master_jabatan_golongan,kode'],
            'nama_label' => ['required', 'string', 'max:100'],
        ], [
            'kategori.required'   => 'Kategori wajib dipilih (JABATAN atau GOLONGAN).',
            'kode.required'       => 'Kode referensi wajib diisi.',
            'kode.unique'         => 'Kode ini sudah terdaftar sebelumnya.',
            'nama_label.required' => 'Nama/label jabatan atau golongan wajib diisi.',
        ]);

        $item = MasterJabatanGolongan::create([
            'kategori'   => strtoupper($validated['kategori']),
            'kode'       => trim($validated['kode']),
            'nama_label' => trim($validated['nama_label']),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Master data ' . $item->kategori . ' berhasil ditambahkan.',
                'data'    => $item,
            ], 201);
        }

        return redirect()->route('admin.master.jabatan-golongan.index')->with('success', 'Master data berhasil ditambahkan.');
    }

    /**
     * Perbarui data master Jabatan / Golongan (Mendukung parameter URL UUID / ID).
     */
    public function update(Request $request, string $id): JsonResponse|Response
    {
        $item = \Illuminate\Support\Str::isUuid($id)
            ? MasterJabatanGolongan::where('uuid', $id)->firstOrFail()
            : MasterJabatanGolongan::findOrFail(is_numeric($id) ? (int)$id : 0);

        $validated = $request->validate([
            'kode'       => ['required', 'string', 'max:50', Rule::unique('master_jabatan_golongan', 'kode')->ignore($item->id)],
            'nama_label' => ['required', 'string', 'max:100'],
        ], [
            'kode.required'       => 'Kode referensi wajib diisi.',
            'kode.unique'         => 'Kode ini sudah terdaftar sebelumnya.',
            'nama_label.required' => 'Nama/label jabatan atau golongan wajib diisi.',
        ]);

        $item->update([
            'kode'       => trim($validated['kode']),
            'nama_label' => trim($validated['nama_label']),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Data master ' . $item->kategori . ' berhasil diperbarui.',
                'data'    => $item,
            ]);
        }

        return redirect()->route('admin.master.jabatan-golongan.index')->with('success', 'Data master berhasil diperbarui.');
    }

    /**
     * Hapus master data jika belum dipakai dalam transaksi pendaftaran (Mendukung parameter URL UUID / ID).
     */
    public function destroy(string $id): JsonResponse
    {
        $item = \Illuminate\Support\Str::isUuid($id)
            ? MasterJabatanGolongan::where('uuid', $id)->firstOrFail()
            : MasterJabatanGolongan::findOrFail(is_numeric($id) ? (int)$id : 0);

        // Periksa apakah item dipakai oleh data pendaftaran
        $isUsed = $item->pendaftaranSebagaiJabatan()->exists() || $item->pendaftaranSebagaiGolongan()->exists();
        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Master data tidak dapat dihapus karena telah digunakan pada berkas pendaftaran ASN.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Master data berhasil dihapus.',
        ]);
    }
}
