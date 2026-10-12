<?php

namespace Tests\Unit;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Services\EmailGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmailGeneratorServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected EmailGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailGeneratorService();
    }

    public function test_clean_full_name_eliminates_academic_and_religious_titles_and_strips_dots_and_commas(): void
    {
        // 1. Gelar Akademik & Keagamaan (Prof., Dr., H., S.Kom., M.Kom., Ph.D.) dihilangkan, tanpa titik/koma
        $this->assertEquals(
            'Ajria Jundan Jahid Idris',
            $this->service->cleanFullName('Prof. Dr. H. Ajria Jundan Jahid Idris, S.Kom., M.Kom., Ph.D.')
        );

        // 2. Gelar Haji & Akademik dihilangkan, nama sesuai KTP
        $this->assertEquals(
            'Budi Santoso',
            $this->service->cleanFullName('Ir. Drs. Haji Budi Santoso, M.Si.')
        );

        // 3. Gelar Hajah / Hj. dan Akademik dihilangkan
        $this->assertEquals(
            'Siti Aisyah',
            $this->service->cleanFullName('Dr. Hj. Siti Aisyah, S.Pd., M.Pd.')
        );

        // 4. Gelar Hajah kata penuh
        $this->assertEquals(
            'Siti Aisyah',
            $this->service->cleanFullName('Hajah Siti Aisyah, S.Pd.')
        );

        // 5. Gelar K.H., KH, Kiai Haji, Ustadz dihilangkan
        $this->assertEquals(
            'Ahmad Dahlan',
            $this->service->cleanFullName('K.H. Ustadz Ahmad Dahlan, M.Pd.')
        );

        $this->assertEquals(
            'Ahmad Dahlan',
            $this->service->cleanFullName('Kiai Haji Ahmad Dahlan')
        );

        $this->assertEquals(
            'Ahmad Dahlan',
            $this->service->cleanFullName('KH Ahmad Dahlan, Lc.')
        );

        // 6. Nama Adat KTP (Raden, Tubagus, Andi) tetap dipertahankan, gelar akademik dihilangkan
        $this->assertEquals(
            'Raden Budi Santoso',
            $this->service->cleanFullName('Ir. Raden Budi Santoso, S.T.')
        );

        $this->assertEquals(
            'Tubagus Hasan',
            $this->service->cleanFullName('Tubagus Hasan, S.T.')
        );

        $this->assertEquals(
            'Andi Pratama',
            $this->service->cleanFullName('Andi Pratama, S.E.')
        );

        // 7. Nama dengan singkatan Moh. atau titik dihilangkan tanda titiknya
        $this->assertEquals(
            'Moh Ma\'ruf',
            $this->service->cleanFullName('Moh. Ma\'ruf, S.Ag.')
        );

        // 8. Nama Satu Kata
        $this->assertEquals(
            'Budi',
            $this->service->cleanFullName('Dr. H. Budi, S.T.')
        );

        // 9. Nama dengan Gelar Diploma / Vokasi
        $this->assertEquals(
            'Dewi Sartika',
            $this->service->cleanFullName('Dewi Sartika, A.Md.Keb.')
        );
    }

    public function test_generates_email_for_multi_word_name_with_3_digit_awal_nip(): void
    {
        // "Ajria Jundan Jahid Idris" NIP "200101012020121001" -> ajria_jundan_200@cirebonkota.go.id
        $email = $this->service->generate('Ajria Jundan Jahid Idris', '200101012020121001');

        $this->assertEquals('ajria_jundan_200@cirebonkota.go.id', $email);
        $this->assertTrue($this->service->isOfficialDomain($email));
    }

    public function test_generates_email_for_single_word_name_with_3_digit_awal_nip(): void
    {
        // "Budi" NIP "199001012020121001" -> budi_199@cirebonkota.go.id
        $email = $this->service->generate('Budi', '199001012020121001');

        $this->assertEquals('budi_199@cirebonkota.go.id', $email);
        $this->assertTrue($this->service->isOfficialDomain($email));
    }

    public function test_eliminates_titles_and_generates_standard_email(): void
    {
        // Input "Dr. Hj. Siti Nurhaliza, M.Pd." NIP "198505052010012002" -> siti_nurhaliza_198@cirebonkota.go.id
        $email = $this->service->generate('Dr. Hj. Siti Nurhaliza, M.Pd.', '198505052010012002');

        $this->assertEquals('siti_nurhaliza_198@cirebonkota.go.id', $email);
    }

    public function test_duplicate_fallback_uses_3_digit_akhir_nip(): void
    {
        $skpd = Skpd::first();

        // 1. Simpan pendaftar pertama yang menggunakan email standar ajria_jundan_200@cirebonkota.go.id
        PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-20260828-TEST01',
            'nama_lengkap'             => 'Ajria Jundan',
            'nik'                      => '3274010101000001',
            'nip'                      => '200101012020121001',
            'no_hp'                    => '081234567890',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'email_usulan'             => 'ajria_jundan_200@cirebonkota.go.id',
            'status'                   => 'MENUNGGU_VERIFIKASI',
            'is_email_recap_confirmed' => false,
        ]);

        // 2. Pendaftar kedua dengan nama sama -> harus fallback ke 3 digit akhir NIP
        $email2 = $this->service->generate('Prof. Dr. H. Ajria Jundan Jahid Idris, S.Kom', '200105052020121002');

        // Harus masuk ke fallback: 3 digit akhir NIP (ajria_jundan_002@cirebonkota.go.id)
        $this->assertEquals('ajria_jundan_002@cirebonkota.go.id', $email2);
    }

    public function test_duplicate_fallback_tahap_2_generates_unique_combination(): void
    {
        $skpd = Skpd::first();

        // Daftarkan pendaftar 1 (pakai 3 digit awal NIP: ajria_jundan_200@cirebonkota.go.id)
        PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-20260828-TEST02',
            'nama_lengkap'             => 'Ajria Jundan',
            'nik'                      => '3274010101000002',
            'nip'                      => '200101012020121001',
            'no_hp'                    => '081234567890',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'email_usulan'             => 'ajria_jundan_200@cirebonkota.go.id',
            'status'                   => 'MENUNGGU_VERIFIKASI',
            'is_email_recap_confirmed' => false,
        ]);

        // Daftarkan pendaftar 2 (pakai 3 digit akhir NIP: ajria_jundan_001@cirebonkota.go.id)
        PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-20260828-TEST03',
            'nama_lengkap'             => 'Ajria Jundan',
            'nik'                      => '3274010101000003',
            'nip'                      => '200101012020121002',
            'no_hp'                    => '081234567891',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'email_usulan'             => 'ajria_jundan_001@cirebonkota.go.id',
            'status'                   => 'MENUNGGU_VERIFIKASI',
            'is_email_recap_confirmed' => false,
        ]);

        // Pendaftar ke-3: harus menghasilkan kombinasi unik Tahap 2
        $email3 = $this->service->generate('Prof. Dr. H. Ajria Jundan Jahid Idris, S.Kom', '200105052020121003');

        $this->assertNotEquals('ajria_jundan_200@cirebonkota.go.id', $email3);
        $this->assertNotEquals('ajria_jundan_001@cirebonkota.go.id', $email3);
        $this->assertTrue($this->service->isOfficialDomain($email3));
        $this->assertFalse($this->service->isEmailTaken($email3));
    }

    public function test_quick_collision_check_detail(): void
    {
        $skpd = Skpd::first();

        PendaftaranTte::create([
            'nomor_referensi'           => 'TTE-20260828-COLL01',
            'nama_lengkap'              => 'Budi Santoso',
            'nik'                       => '3274010101000004',
            'nip'                       => '199001012020121004',
            'no_hp'                     => '081234567894',
            'skpd_id'                   => $skpd->id,
            'status_memiliki_email'     => true,
            'email_final_terverifikasi' => 'budi_santoso_199@cirebonkota.go.id',
            'status'                    => 'DISETUJUI',
            'is_email_recap_confirmed'  => true,
        ]);

        // Cek email yang bentrok
        $collision = $this->service->checkCollisionDetail('budi_santoso_199@cirebonkota.go.id');
        $this->assertFalse($collision['is_available']);
        $this->assertEquals('pendaftaran_tte', $collision['reason']);
        $this->assertStringContainsString('Budi Santoso', $collision['message']);

        // Cek email yang aman
        $available = $this->service->checkCollisionDetail('budi_santoso_004@cirebonkota.go.id');
        $this->assertTrue($available['is_available']);
        $this->assertNull($available['reason']);
    }

    public function test_validates_official_domain(): void
    {
        $this->assertTrue($this->service->isOfficialDomain('user@cirebonkota.go.id'));
        $this->assertTrue($this->service->isOfficialDomain('USER@CIREBONKOTA.GO.ID'));
        $this->assertFalse($this->service->isOfficialDomain('user@gmail.com'));
        $this->assertFalse($this->service->isOfficialDomain('user@othergov.go.id'));
    }
}
