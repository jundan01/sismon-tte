<?php

namespace Database\Seeders;

use App\Domain\Master\Models\MasterJabatanGolongan;
use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use App\Models\User;
use App\Services\EmailGeneratorService;
use Illuminate\Database\Seeder;

class DummyTestingDataSeeder extends Seeder
{
    public function run(): void
    {
        /** @var EmailGeneratorService $emailGenerator */
        $emailGenerator = app(EmailGeneratorService::class);

        $skpds = Skpd::all();
        $jabatans = MasterJabatanGolongan::where('kategori', 'JABATAN')->get();
        $golongans = MasterJabatanGolongan::where('kategori', 'GOLONGAN')->get();
        $admin = User::where('email', 'ajriajundan@gmail.com')->first() ?: User::first();

        if ($skpds->isEmpty() || $jabatans->isEmpty()) {
            return;
        }

        $namaList = [
            ['Drs. H. Agus Supriyadi, M.Si', 'L'],
            ['Dr. Hj. Siti Fatimah, M.Pd', 'P'],
            ['Budi Santoso, S.STP, M.Si', 'L'],
            ['Ratna Dewi Kartika, S.E', 'P'],
            ['Ir. Hendra Gunawan, M.T', 'L'],
            ['Dewi Sartika, S.Kep, Ners', 'P'],
            ['Ahmad Hidayat, S.Kom', 'L'],
            ['Nurul Aini, S.Sos, M.AP', 'P'],
            ['dr. Eko Prasetyo, Sp.PD', 'L'],
            ['Sri Wahyuni, S.Pd, M.M', 'P'],
            ['Bambang Triyono, S.H, M.H', 'L'],
            ['Endang Rahayu, S.Farm, Apt', 'P'],
            ['Dedi Suryadi, S.AP', 'L'],
            ['drg. Maya Rosmayanti', 'P'],
            ['Asep Sunandar, S.T', 'L'],
            ['Rina Marlina, S.Psi', 'P'],
            ['Fajar Nugraha, S.IP', 'L'],
            ['Ika Puspitasari, S.Si', 'P'],
            ['Rian Ardiansyah, S.Tr.Pel', 'L'],
            ['Yanti Kusmayanti, S.Gz', 'P'],
            ['Dr. Wahyu Hidayat, M.Kes', 'L'],
            ['Tini Kartini, S.Kom', 'P'],
            ['Mulyadi, S.Pd', 'L'],
            ['Lilis Suryani, S.ST', 'P'],
            ['Agung Permana, S.H', 'L'],
            ['Rismawati, S.E, M.Ak', 'P'],
            ['K.H. Imam Buchori, S.Ag', 'L'],
            ['Anita Susanti, S.Kep', 'P'],
            ['Rahmat Setiawan, S.T', 'L'],
            ['Lia Dahlia, S.Sos', 'P'],
            ['Drs. Cecep Supriatna', 'L'],
            ['Nani Suryani, S.Pd.SD', 'P'],
            ['Heri Hermawan, S.Kom', 'L'],
            ['Fitri Handayani, S.Tr.Kom', 'P'],
            ['Ustadz Nanang Qosim, S.H.I', 'L'],
            ['Sri Mulyati, A.Md.Keb', 'P'],
            ['Yayan Hendrayana, S.Sos', 'L'],
            ['Neneng Hasanah, S.Pd', 'P'],
            ['Ade Suhendar, S.T', 'L'],
            ['Eni Nuraeni, S.IP', 'P'],
            ['Gunawan Wibisono, S.H', 'L'],
            ['Wati Kusmawati, S.M', 'P'],
            ['Sopian Hadi, S.Kom', 'L'],
            ['Rini Anggraeni, S.Tr.Akun', 'P'],
            ['Teguh Santoso, S.IP', 'L'],
            ['Desi Ratnasari, S.Pd', 'P'],
            ['Lukman Hakim, S.T', 'L'],
            ['Yuliana Safitri, S.Psi', 'P'],
            ['Roni Gunawan, S.Kom', 'L'],
            ['Siti Maryam, S.Pd.I', 'P'],
        ];

        foreach ($namaList as $index => [$rawNama, $gender]) {
            $num = str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT);
            $nik = '327401' . ($gender === 'P' ? '45089' : '15089') . $num;
            $nip = '198808' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) . '201503' . ($gender === 'P' ? '2' : '1') . '00' . ($index + 1);
            $skpd = $skpds[$index % $skpds->count()];
            $jabatan = $jabatans[$index % $jabatans->count()];
            $golongan = $golongans[$index % $golongans->count()];

            // 1. Sanitasi nama lengkap murni tanpa gelar & tanda baca (sesuai standar KTP)
            $cleanNamaLengkap = $emailGenerator->cleanFullName($rawNama);

            // 2. Tentukan distribusi stage data demo
            if ($index < 15) {
                $stage = 'MENUNGGU_VERIFIKASI';
                $pendaftaranStatus = 'MENUNGGU_VERIFIKASI';
            } elseif ($index < 25) {
                $stage = 'DISETUJUI';
                $pendaftaranStatus = 'DISETUJUI';
            } elseif ($index < 30) {
                $stage = 'DITOLAK';
                $pendaftaranStatus = 'DITOLAK';
            } elseif ($index < 42) {
                $stage = 'AKTIF';
                $pendaftaranStatus = 'DISETUJUI';
            } elseif ($index < 47) {
                $stage = 'WARNING_H3';
                $pendaftaranStatus = 'DISETUJUI';
            } else {
                $stage = 'EXPIRED';
                $pendaftaranStatus = 'DISETUJUI';
            }

            // 3. Logika usulan email dinas sesuai EmailGeneratorService
            $hasEmail = ($index % 2 === 0);
            $nameWords = $emailGenerator->extractNameWords($cleanNamaLengkap);

            if ($hasEmail) {
                // Email dinas yang sudah ada sebelumnya (3 digit awal NIP)
                $existingUsername = count($nameWords) >= 2
                    ? "{$nameWords[0]}_{$nameWords[1]}"
                    : "{$nameWords[0]}_" . substr($nip, 0, 3);
                $emailExisting = "{$existingUsername}@cirebonkota.go.id";
                $emailUsulan = null;
            } else {
                // Belum memiliki email dinas -> Di-generate otomatis via generator
                $emailExisting = null;
                $emailUsulan = $emailGenerator->generate($cleanNamaLengkap, $nip);
            }

            $emailFinal = in_array($stage, ['DISETUJUI', 'AKTIF', 'WARNING_H3', 'EXPIRED'])
                ? ($emailExisting ?: $emailUsulan)
                : null;

            // 4. Simpan data pendaftaran
            $pendaftaran = PendaftaranTte::create([
                'nomor_referensi'          => 'TTE-' . date('Ymd') . '-TS' . $num,
                'nama_lengkap'             => $cleanNamaLengkap, // Bersih tanpa gelar & titik/koma
                'nik'                      => $nik,
                'nip'                      => $nip,
                'no_hp'                    => '08' . rand(1111111111, 9999999999),
                'skpd_id'                  => $skpd->id,
                'jabatan_id'               => $jabatan->id,
                'golongan_id'              => $golongan->id,
                'status_memiliki_email'    => $hasEmail,
                'email_existing'           => $emailExisting,
                'email_usulan'             => $emailUsulan,
                'email_final_terverifikasi'=> $emailFinal,
                'is_email_recap_confirmed' => in_array($stage, ['DISETUJUI', 'AKTIF', 'WARNING_H3', 'EXPIRED']),
                'status'                   => $pendaftaranStatus,
                'catatan_penolakan'        => $stage === 'DITOLAK' ? 'Lampiran SK Pangkat tidak terbaca jelas. Mohon upload ulang dengan resolusi memadai.' : null,
                'verified_by'              => in_array($stage, ['DISETUJUI', 'AKTIF', 'WARNING_H3', 'EXPIRED']) ? $admin->id : null,
                'verified_at'              => in_array($stage, ['DISETUJUI', 'AKTIF', 'WARNING_H3', 'EXPIRED']) ? now()->subDays(rand(5, 30)) : null,
                'verified_ip'              => in_array($stage, ['DISETUJUI', 'AKTIF', 'WARNING_H3', 'EXPIRED']) ? '10.10.1.50' : null,
            ]);

            // 5. Simpan data sertifikat (untuk yang berstatus AKTIF, WARNING_H3, EXPIRED)
            if (in_array($stage, ['AKTIF', 'WARNING_H3', 'EXPIRED'])) {
                if ($stage === 'AKTIF') {
                    $tglTerbit = now()->subMonths(rand(1, 18))->toDateString();
                    $tglKadaluarsa = now()->subMonths(rand(1, 18))->addYears(2)->toDateString();
                    $certStatus = 'AKTIF';
                } elseif ($stage === 'WARNING_H3') {
                    $daysRemaining = rand(1, 3);
                    $tglTerbit = now()->subYears(2)->addDays($daysRemaining)->toDateString();
                    $tglKadaluarsa = now()->addDays($daysRemaining)->toDateString();
                    $certStatus = 'WARNING_H3';
                } else { // EXPIRED
                    $tglTerbit = now()->subYears(2)->subDays(rand(5, 30))->toDateString();
                    $tglKadaluarsa = now()->subDays(rand(5, 30))->toDateString();
                    $certStatus = 'EXPIRED';
                }

                SertifikatTte::create([
                    'pendaftaran_id'    => $pendaftaran->id,
                    'email_dinas'       => $emailFinal ?: $emailGenerator->generate($cleanNamaLengkap, $nip),
                    'tanggal_penerbitan'=> $tglTerbit,
                    'tanggal_kadaluarsa'=> $tglKadaluarsa,
                    'status_sertifikat' => $certStatus,
                    'verified_by_tahap2'=> $admin->id,
                    'verified_at_tahap2'=> now()->subDays(rand(1, 10)),
                    'verified_ip_tahap2'=> '10.10.1.50',
                ]);
            }
        }
    }
}