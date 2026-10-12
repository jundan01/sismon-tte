<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memperkaya master sub-unit kerja Dinas Pendidikan dengan unit sekolah spesifik (SMP Negeri,
     * Sekolah Khusus / SKh, Korwil Wilayah SD Negeri, Sanggar Kegiatan Belajar).
     */
    public function up(): void
    {
        $disdik = DB::table('skpd')
            ->where('kode_skpd', 'DISDIK')
            ->orWhere('nama_skpd', 'ilike', '%pendidikan%')
            ->first();

        if (!$disdik) {
            return;
        }

        // Hapus entri generik sementara Disdik yang lama
        DB::table('skpd_sub_units')
            ->where('skpd_id', $disdik->id)
            ->whereIn('nama_sub_unit', [
                'Satuan Pendidikan SD Negeri',
                'Satuan Pendidikan SMP Negeri',
                'Dinas Pendidikan (Kantor Induk)',
            ])
            ->delete();

        // Daftar Sub-Unit & Sekolah Definitif di bawah Dinas Pendidikan Kota Cirebon
        $disdikUnits = [
            ['nama' => 'Dinas Pendidikan (Kantor Induk & Sekretariat)', 'kategori' => 'DINAS_INDUK', 'target' => 45],
            ['nama' => 'SMP Negeri 1 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 52],
            ['nama' => 'SMP Negeri 2 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 46],
            ['nama' => 'SMP Negeri 3 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 42],
            ['nama' => 'SMP Negeri 4 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 44],
            ['nama' => 'SMP Negeri 5 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 40],
            ['nama' => 'SMP Negeri 6 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 38],
            ['nama' => 'SMP Negeri 7 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 36],
            ['nama' => 'SMP Negeri 8 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 34],
            ['nama' => 'SMP Negeri 9 Kota Cirebon',                     'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 32],
            ['nama' => 'SKh Negeri Kota Cirebon (Sekolah Khusus / SLB)', 'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 26],
            ['nama' => 'Korwil Bidang Pendidikan Kec. Harjamukti (SD)', 'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 120],
            ['nama' => 'Korwil Bidang Pendidikan Kec. Kesambi (SD)',    'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 105],
            ['nama' => 'Korwil Bidang Pendidikan Kec. Kejaksan (SD)',   'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 90],
            ['nama' => 'Korwil Bidang Pendidikan Kec. Lemahwungkuk (SD)','kategori' => 'SATUAN_PENDIDIKAN', 'target' => 75],
            ['nama' => 'Korwil Bidang Pendidikan Kec. Pekalipan (SD)',  'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 65],
            ['nama' => 'SPNF SKB Kota Cirebon (Sanggar Kegiatan Belajar)','kategori' => 'SATUAN_PENDIDIKAN', 'target' => 18],
            ['nama' => 'Penugasan Unit / Lembaga Eksternal',            'kategori' => 'PENUGASAN_EKSTERNAL', 'target' => 12],
        ];

        $totalAsnDisdik = 0;
        foreach ($disdikUnits as $unit) {
            $totalAsnDisdik += $unit['target'];

            DB::table('skpd_sub_units')->updateOrInsert(
                [
                    'skpd_id'       => $disdik->id,
                    'nama_sub_unit' => $unit['nama'],
                ],
                [
                    'uuid'                => Str::uuid()->toString(),
                    'kategori_penempatan' => $unit['kategori'],
                    'target_asn'          => $unit['target'],
                    'is_active'           => true,
                    'updated_at'          => now(),
                    'created_at'          => now(),
                ]
            );
        }

        // Perbarui total_asn Dinas Pendidikan di tabel skpd agar presisi
        DB::table('skpd')->where('id', $disdik->id)->update(['total_asn' => $totalAsnDisdik]);

        // Distribusikan pendaftaran_tte yang menginduk ke Dinas Pendidikan
        $disdikSubUnits = DB::table('skpd_sub_units')
            ->where('skpd_id', $disdik->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($disdikSubUnits->isNotEmpty()) {
            $pendaftarDisdik = DB::table('pendaftaran_tte')
                ->where('skpd_id', $disdik->id)
                ->get();

            foreach ($pendaftarDisdik as $idx => $p) {
                $chosen = $disdikSubUnits[$idx % $disdikSubUnits->count()];
                DB::table('pendaftaran_tte')
                    ->where('id', $p->id)
                    ->update([
                        'sub_kategori_asn' => $chosen->kategori_penempatan,
                        'sub_unit_kerja'   => $chosen->nama_sub_unit,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback didukung
    }
};
