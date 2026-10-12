<?php

namespace Database\Seeders;

use App\Domain\Master\Models\Skpd;
use Illuminate\Database\Seeder;

/**
 * Seeder data SKPD — Satuan Kerja Perangkat Daerah Kota Cirebon.
 */
class SkpdSeeder extends Seeder
{
    public function run(): void
    {
        $dataSkpd = [
            ['kode_skpd' => 'DKIS',          'nama_skpd' => 'Dinas Komunikasi, Informatika, dan Statistik',                 'total_asn' => 45],
            ['kode_skpd' => 'BAPPEDA',       'nama_skpd' => 'Badan Perencanaan Pembangunan Daerah',                         'total_asn' => 55],
            ['kode_skpd' => 'BPKAD',         'nama_skpd' => 'Badan Pengelolaan Keuangan dan Aset Daerah',                   'total_asn' => 65],
            ['kode_skpd' => 'BKPSDM',        'nama_skpd' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',       'total_asn' => 50],
            ['kode_skpd' => 'DINKES',        'nama_skpd' => 'Dinas Kesehatan',                                              'total_asn' => 120],
            ['kode_skpd' => 'DISDIK',        'nama_skpd' => 'Dinas Pendidikan',                                             'total_asn' => 200],
            ['kode_skpd' => 'DPUPR',         'nama_skpd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',                      'total_asn' => 85],
            ['kode_skpd' => 'DISHUB',        'nama_skpd' => 'Dinas Perhubungan',                                            'total_asn' => 60],
            ['kode_skpd' => 'DINSOSPPPA',    'nama_skpd' => 'Dinas Sosial, Pemberdayaan Perempuan dan Perlindungan Anak',   'total_asn' => 40],
            ['kode_skpd' => 'DINPERKIM',     'nama_skpd' => 'Dinas Perumahan Rakyat dan Kawasan Permukiman',                'total_asn' => 50],
            ['kode_skpd' => 'DLHK',          'nama_skpd' => 'Dinas Lingkungan Hidup dan Kebersihan',                        'total_asn' => 75],
            ['kode_skpd' => 'DISDUKCAPIL',   'nama_skpd' => 'Dinas Kependudukan dan Pencatatan Sipil',                     'total_asn' => 55],
            ['kode_skpd' => 'DPMPTSP',       'nama_skpd' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',      'total_asn' => 40],
            ['kode_skpd' => 'DINPERIN',      'nama_skpd' => 'Dinas Perindustrian dan Perdagangan',                         'total_asn' => 35],
            ['kode_skpd' => 'INSPEKTORAT',   'nama_skpd' => 'Inspektorat Daerah',                                          'total_asn' => 30],
            ['kode_skpd' => 'SETDA',         'nama_skpd' => 'Sekretariat Daerah',                                          'total_asn' => 90],
            ['kode_skpd' => 'SETWAN',        'nama_skpd' => 'Sekretariat DPRD',                                            'total_asn' => 35],
            ['kode_skpd' => 'KECAMATAN-LR',  'nama_skpd' => 'Kecamatan Lemahwungkuk',                                     'total_asn' => 25],
            ['kode_skpd' => 'KECAMATAN-KJ',  'nama_skpd' => 'Kecamatan Kejaksan',                                         'total_asn' => 25],
            ['kode_skpd' => 'KECAMATAN-PS',  'nama_skpd' => 'Kecamatan Pekalipan',                                        'total_asn' => 20],
        ];

        foreach ($dataSkpd as $skpd) {
            Skpd::updateOrCreate(
                ['kode_skpd' => $skpd['kode_skpd']],
                array_merge($skpd, ['is_active' => true])
            );
        }
    }
}
