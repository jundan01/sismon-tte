<?php

namespace App\Http\Controllers;

use App\Domain\Master\Models\Skpd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Manajemen Master Data Unit Kerja (SKPD).
 * Ref: PRD.md FR-18, SCHEMA.md §7.1, ARCHITECTURE.md §6.5
 */
class MasterSkpdController extends Controller
{
    /**
     * Tampilkan daftar master SKPD.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        $search = $request->input('q');

        $skpdList = Skpd::withCount('pendaftaranTte')
            ->when($search, function ($q, $search) {
                $q->where('nama_skpd', 'ilike', "%{$search}%")
                  ->orWhere('kode_skpd', 'ilike', "%{$search}%");
            })
            ->orderBy('nama_skpd', 'asc')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $skpdList,
            ]);
        }

        return view('admin.master.skpd.index', [
            'skpdList' => $skpdList,
        ]);
    }

    /**
     * Tambah master SKPD baru.
     */
    public function store(Request $request): JsonResponse|Response
    {
        $validated = $request->validate([
            'kode_skpd' => ['required', 'string', 'max:50', 'unique:skpd,kode_skpd'],
            'nama_skpd' => ['required', 'string', 'max:255'],
            'total_asn' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode_skpd.required' => 'Kode SKPD wajib diisi.',
            'kode_skpd.unique'   => 'Kode SKPD ini sudah terdaftar sebelumnya.',
            'nama_skpd.required' => 'Nama instansi/SKPD wajib diisi.',
        ]);

        $skpd = Skpd::create([
            'kode_skpd' => strtoupper(trim($validated['kode_skpd'])),
            'nama_skpd' => trim($validated['nama_skpd']),
            'total_asn' => $validated['total_asn'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Master SKPD berhasil ditambahkan.',
                'data'    => $skpd,
            ], 201);
        }

        return redirect()->route('admin.master.skpd.index')->with('success', 'Master SKPD berhasil ditambahkan.');
    }

    /**
     * Perbarui data master SKPD (Mendukung parameter URL UUID / ID).
     */
    public function update(Request $request, string $id): JsonResponse|Response
    {
        $skpd = \Illuminate\Support\Str::isUuid($id)
            ? Skpd::where('uuid', $id)->firstOrFail()
            : Skpd::findOrFail(is_numeric($id) ? (int)$id : 0);

        $validated = $request->validate([
            'kode_skpd' => ['required', 'string', 'max:50', Rule::unique('skpd', 'kode_skpd')->ignore($skpd->id)],
            'nama_skpd' => ['required', 'string', 'max:255'],
            'total_asn' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode_skpd.required' => 'Kode SKPD wajib diisi.',
            'kode_skpd.unique'   => 'Kode SKPD ini sudah terdaftar sebelumnya.',
            'nama_skpd.required' => 'Nama instansi/SKPD wajib diisi.',
        ]);

        $skpd->update([
            'kode_skpd' => strtoupper(trim($validated['kode_skpd'])),
            'nama_skpd' => trim($validated['nama_skpd']),
            'total_asn' => $validated['total_asn'] ?? $skpd->total_asn,
            'is_active' => $request->boolean('is_active', $skpd->is_active),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Data master SKPD berhasil diperbarui.',
                'data'    => $skpd,
            ]);
        }

        return redirect()->route('admin.master.skpd.index')->with('success', 'Data master SKPD berhasil diperbarui.');
    }

    /**
     * Aktifkan / Nonaktifkan status master SKPD (Mendukung parameter URL UUID / ID).
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $skpd = \Illuminate\Support\Str::isUuid($id)
            ? Skpd::where('uuid', $id)->firstOrFail()
            : Skpd::findOrFail(is_numeric($id) ? (int)$id : 0);

        $skpd->update(['is_active' => !$skpd->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => 'Status SKPD berhasil diubah menjadi ' . ($skpd->is_active ? 'Aktif' : 'Nonaktif') . '.',
            'is_active' => $skpd->is_active,
        ]);
    }
}
