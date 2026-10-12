<?php

namespace Database\Seeders;

use App\Domain\Master\Models\MasterJabatanGolongan;
use Illuminate\Database\Seeder;

/**
 * Seeder data Master Jabatan & Golongan ASN.
 */
class MasterJabatanGolonganSeeder extends Seeder
{
    public function run(): void
    {
        // ── Data Master Jabatan ──────────────────────────────────
        $jabatan = [
            ['kode' => 'JAB-001', 'nama_label' => 'Kepala Dinas'],
            ['kode' => 'JAB-002', 'nama_label' => 'Sekretaris Dinas'],
            ['kode' => 'JAB-003', 'nama_label' => 'Kepala Bidang'],
            ['kode' => 'JAB-004', 'nama_label' => 'Kepala Seksi'],
            ['kode' => 'JAB-005', 'nama_label' => 'Kepala Sub Bagian'],
            ['kode' => 'JAB-006', 'nama_label' => 'Analis Kebijakan'],
            ['kode' => 'JAB-007', 'nama_label' => 'Pranata Komputer'],
            ['kode' => 'JAB-008', 'nama_label' => 'Sandiman'],
            ['kode' => 'JAB-009', 'nama_label' => 'Fungsional Umum'],
            ['kode' => 'JAB-010', 'nama_label' => 'Staf'],
        ];

        foreach ($jabatan as $item) {
            MasterJabatanGolongan::firstOrCreate(
                ['kode' => $item['kode']],
                array_merge($item, ['kategori' => 'JABATAN'])
            );
        }

        // ── Data Master Golongan / Pangkat ───────────────────────
        $golongan = [
            ['kode' => 'I/a',   'nama_label' => 'Juru Muda'],
            ['kode' => 'I/b',   'nama_label' => 'Juru Muda Tingkat 1'],
            ['kode' => 'I/c',   'nama_label' => 'Juru'],
            ['kode' => 'I/d',   'nama_label' => 'Juru Tingkat 1'],
            ['kode' => 'II/a',  'nama_label' => 'Pengatur Muda'],
            ['kode' => 'II/b',  'nama_label' => 'Pengatur Muda Tingkat 1'],
            ['kode' => 'II/c',  'nama_label' => 'Pengatur'],
            ['kode' => 'II/d',  'nama_label' => 'Pengatur Tingkat 1'],
            ['kode' => 'III/a', 'nama_label' => 'Penata Muda'],
            ['kode' => 'III/b', 'nama_label' => 'Penata Muda Tingkat 1'],
            ['kode' => 'III/c', 'nama_label' => 'Penata'],
            ['kode' => 'III/d', 'nama_label' => 'Penata Tingkat 1'],
            ['kode' => 'IV/a',  'nama_label' => 'Pembina'],
            ['kode' => 'IV/b',  'nama_label' => 'Pembina Tingkat 1'],
            ['kode' => 'IV/c',  'nama_label' => 'Pembina Utama Muda'],
            ['kode' => 'IV/d',  'nama_label' => 'Pembina Utama Madya'],
            ['kode' => 'IV/e',  'nama_label' => 'Pembina Utama'],
        ];

        foreach ($golongan as $item) {
            MasterJabatanGolongan::firstOrCreate(
                ['kode' => $item['kode']],
                array_merge($item, ['kategori' => 'GOLONGAN'])
            );
        }
    }
}
