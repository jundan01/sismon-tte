<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan tabel `skpd_sub_units` dan kolom `sub_kategori_asn` & `sub_unit_kerja` pada `pendaftaran_tte`.
     */
    public function up(): void
    {
        // 1. Buat tabel master sub-unit kerja di bawah SKPD
        if (!Schema::hasTable('skpd_sub_units')) {
            Schema::create('skpd_sub_units', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('skpd_id');
                $table->string('nama_sub_unit', 255);
                $table->string('kategori_penempatan', 100)->default('DINAS_INDUK');
                $table->unsignedInteger('target_asn')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('updated_at')->useCurrent();

                $table->foreign('skpd_id')->references('id')->on('skpd')->cascadeOnDelete();
                $table->index(['skpd_id', 'kategori_penempatan'], 'idx_skpd_sub_units_kategori');
            });
        }

        // 2. Tambahkan kolom sub-kategori pada `pendaftaran_tte`
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            if (!Schema::hasColumn('pendaftaran_tte', 'sub_kategori_asn')) {
                $table->string('sub_kategori_asn', 100)->default('DINAS_INDUK')->after('skpd_id');
            }
            if (!Schema::hasColumn('pendaftaran_tte', 'sub_unit_kerja')) {
                $table->string('sub_unit_kerja', 255)->nullable()->default('Kantor Induk')->after('sub_kategori_asn');
            }
        });

        // Index untuk agregasi cepat per sub-kategori di PostgreSQL
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            $table->index(['skpd_id', 'sub_kategori_asn'], 'idx_pendaftaran_skpd_sub_kategori');
            $table->index(['skpd_id', 'sub_unit_kerja'], 'idx_pendaftaran_skpd_sub_unit');
        });

        // 3. Seed sub-unit default untuk seluruh SKPD
        $skpds = DB::table('skpd')->get();
        foreach ($skpds as $skpd) {
            $kode = strtoupper($skpd->kode_skpd);

            if ($kode === 'DINKES') {
                $subUnits = [
                    ['nama' => 'Dinas Kesehatan (Kantor Induk)', 'kategori' => 'DINAS_INDUK', 'target' => 35],
                    ['nama' => 'RSUD Gunung Jati', 'kategori' => 'RSUD', 'target' => 45],
                    ['nama' => 'UPTD Puskesmas Kesambi', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 8],
                    ['nama' => 'UPTD Puskesmas Kejaksan', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 8],
                    ['nama' => 'UPTD Puskesmas Pekalipan', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 8],
                    ['nama' => 'UPTD Puskesmas Lemahwungkuk', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 8],
                    ['nama' => 'Labkesda Kota Cirebon', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 5],
                    ['nama' => 'Penugasan Unit / Lembaga Eksternal', 'kategori' => 'PENUGASAN_EKSTERNAL', 'target' => 3],
                ];
            } elseif ($kode === 'DISDIK') {
                $subUnits = [
                    ['nama' => 'Dinas Pendidikan (Kantor Induk)', 'kategori' => 'DINAS_INDUK', 'target' => 40],
                    ['nama' => 'Satuan Pendidikan SD Negeri', 'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 90],
                    ['nama' => 'Satuan Pendidikan SMP Negeri', 'kategori' => 'SATUAN_PENDIDIKAN', 'target' => 60],
                    ['nama' => 'Penugasan Unit / Lembaga Eksternal', 'kategori' => 'PENUGASAN_EKSTERNAL', 'target' => 10],
                ];
            } elseif (str_starts_with($kode, 'KECAMATAN')) {
                $namaKec = str_replace('Kecamatan ', '', $skpd->nama_skpd);
                $subUnits = [
                    ['nama' => "Kantor Kecamatan {$namaKec} (Induk)", 'kategori' => 'DINAS_INDUK', 'target' => 12],
                    ['nama' => 'Kelurahan Jajaran', 'kategori' => 'KELURAHAN', 'target' => 10],
                    ['nama' => 'Penugasan Unit / Lembaga Eksternal', 'kategori' => 'PENUGASAN_EKSTERNAL', 'target' => 3],
                ];
            } elseif ($kode === 'DISHUB') {
                $subUnits = [
                    ['nama' => 'Dinas Perhubungan (Kantor Induk)', 'kategori' => 'DINAS_INDUK', 'target' => 35],
                    ['nama' => 'UPTD Pengujian Kendaraan Bermotor (PKB)', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 15],
                    ['nama' => 'UPTD Terminal & Perparkiran', 'kategori' => 'UPTD_PUSKESMAS', 'target' => 8],
                    ['nama' => 'Penugasan Unit / Lembaga Eksternal', 'kategori' => 'PENUGASAN_EKSTERNAL', 'target' => 2],
                ];
            } else {
                $subUnits = [
                    ['nama' => "{$skpd->nama_skpd} (Kantor Induk)", 'kategori' => 'DINAS_INDUK', 'target' => (int)($skpd->total_asn * 0.75)],
                    ['nama' => 'UPTD / Unit Layanan Teknis', 'kategori' => 'UPTD_PUSKESMAS', 'target' => (int)($skpd->total_asn * 0.20)],
                    ['nama' => 'Penugasan Unit / Lembaga Eksternal', 'kategori' => 'PENUGASAN_EKSTERNAL', 'target' => max(1, (int)($skpd->total_asn * 0.05))],
                ];
            }

            foreach ($subUnits as $su) {
                DB::table('skpd_sub_units')->insert([
                    'uuid'                => Str::uuid()->toString(),
                    'skpd_id'             => $skpd->id,
                    'nama_sub_unit'       => $su['nama'],
                    'kategori_penempatan' => $su['kategori'],
                    'target_asn'          => $su['target'] ?? null,
                    'is_active'           => true,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }

        // 4. Update data pendaftaran_tte yang sudah ada agar terdistribusi realistis
        $pendaftarans = DB::table('pendaftaran_tte')->get();
        foreach ($pendaftarans as $p) {
            $subUnitsForSkpd = DB::table('skpd_sub_units')
                ->where('skpd_id', $p->skpd_id)
                ->orderBy('id')
                ->get();

            if ($subUnitsForSkpd->isNotEmpty()) {
                $chosen = $subUnitsForSkpd[$p->id % $subUnitsForSkpd->count()];
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
        Schema::table('pendaftaran_tte', function (Blueprint $table) {
            $table->dropIndex('idx_pendaftaran_skpd_sub_kategori');
            $table->dropIndex('idx_pendaftaran_skpd_sub_unit');
            $table->dropColumn(['sub_kategori_asn', 'sub_unit_kerja']);
        });

        Schema::dropIfExists('skpd_sub_units');
    }
};
