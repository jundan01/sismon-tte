<?php

namespace Tests\Feature;

use App\Domain\Analytics\Actions\RefreshDailyAggregatorAction;
use App\Domain\Master\Models\MasterJabatanGolongan;
use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use App\Jobs\UpdateSertifikatStatusJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test Suite Komprehensif Fitur V6:
 * 1. Modul Analitik & Pelaporan (Breakdown Dinamis Seluruh SKPD)
 * 2. Logika Bisnis & Notifikasi H-7
 * 3. Backend & Architecture (UUID / UID untuk URL)
 */
class V6EnhancementsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Skpd $skpd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@cirebonkota.go.id')->firstOrFail();
        $this->skpd = Skpd::firstOrFail();
    }

    public function test_models_auto_generate_valid_v4_uuids(): void
    {
        // 1. Skpd
        $newSkpd = Skpd::create([
            'kode_skpd' => 'SKPD_TEST_' . random_int(100, 999),
            'nama_skpd' => 'Dinas Pengujian V6 Otomatis',
            'singkatan' => 'DPOV6',
            'total_asn' => 50,
            'is_active' => true,
        ]);
        $this->assertNotEmpty($newSkpd->uuid);
        $this->assertTrue(Str::isUuid($newSkpd->uuid));

        // 2. MasterJabatanGolongan
        $newMaster = MasterJabatanGolongan::create([
            'kategori'   => 'JABATAN',
            'kode'       => 'TEST_JAB_' . random_int(100, 999),
            'nama_label' => 'Pranata Komputer Madya',
        ]);
        $this->assertNotEmpty($newMaster->uuid);
        $this->assertTrue(Str::isUuid($newMaster->uuid));

        // 3. User
        $newUser = User::create([
            'name'           => 'Admin Tester V6',
            'email'          => 'tester.v6.' . random_int(1000, 9999) . '@cirebonkota.go.id',
            'password'       => 'P@ssword123!',
            'role'           => 'superadmin',
            'is_mfa_enabled' => false,
        ]);
        $this->assertNotEmpty($newUser->uuid);
        $this->assertTrue(Str::isUuid($newUser->uuid));

        // 4. PendaftaranTte
        $randomSuffix = random_int(1000, 9999);
        $pendaftaran = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-' . date('Ymd') . '-V6' . $randomSuffix,
            'nama_lengkap'          => 'Ahmad Syarifudin',
            'nik'                   => '327401010190' . $randomSuffix,
            'nip'                   => '19900101202012' . $randomSuffix,
            'no_hp'                 => '081234567890',
            'skpd_id'               => $this->skpd->id,
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);
        $this->assertNotEmpty($pendaftaran->uuid);
        $this->assertTrue(Str::isUuid($pendaftaran->uuid));

        // 5. SertifikatTte
        $cert = SertifikatTte::create([
            'pendaftaran_id'     => $pendaftaran->id,
            'email_dinas'        => "ahmad.syarifudin{$randomSuffix}@cirebonkota.go.id",
            'tanggal_penerbitan' => Carbon::today()->toDateString(),
            'tanggal_kadaluarsa' => Carbon::today()->addYears(2)->toDateString(),
            'status_sertifikat'  => 'AKTIF',
            'verified_by_tahap2' => $this->admin->id,
        ]);
        $this->assertNotEmpty($cert->uuid);
        $this->assertTrue(Str::isUuid($cert->uuid));
    }

    public function test_dynamic_skpd_breakdown_endpoint_returns_hierarchical_data_via_uuid(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/admin/capaian-skpd/{$this->skpd->uuid}/breakdown");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'skpd' => [
                'id',
                'uuid',
                'kode_skpd',
                'nama_skpd',
                'total_asn',
                'total_pendaftar',
                'total_disetujui',
                'total_antrian',
                'total_ditolak',
                'persentase_capaian',
            ],
            'hierarki' => [
                'struktural' => ['total', 'disetujui', 'antrian'],
                'fungsional' => ['total', 'disetujui', 'antrian'],
                'pelaksana'  => ['total', 'disetujui', 'antrian'],
            ],
            'breakdown_sub_kategori',
            'kategori_makro',
            'breakdown_jabatan',
            'breakdown_golongan',
            'golongan_tingkat',
            'pemohon_terbaru',
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_dynamic_skpd_sub_kategori_drill_down_and_filtering_for_dinkes(): void
    {
        // Cari atau gunakan SKPD Dinas Kesehatan (atau SKPD uji)
        $dinkes = Skpd::where('kode_skpd', 'DINKES')
            ->orWhere('nama_skpd', 'ilike', '%kesehatan%')
            ->first() ?? $this->skpd;

        $randomSuffix = random_int(1000, 9999);

        // 1. Buat pemohon pada Deskripsi RSUD
        $pendaftarRsud = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-RSUD-' . $randomSuffix,
            'nama_lengkap'          => 'dr. Hendra Spesialis ' . $randomSuffix,
            'nik'                   => '327401010190' . $randomSuffix,
            'nip'                   => '19850101201501' . $randomSuffix,
            'no_hp'                 => '081234567895',
            'skpd_id'               => $dinkes->id,
            'deskripsi'             => 'RSUD Gunung Jati',
            'status_memiliki_email' => false,
            'status'                => 'DISETUJUI',
        ]);

        // 2. Buat pemohon pada Deskripsi Puskesmas
        $pendaftarPuskesmas = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-PKM-' . $randomSuffix,
            'nama_lengkap'          => 'drg. Ratna Puskesmas ' . $randomSuffix,
            'nik'                   => '327401010191' . $randomSuffix,
            'nip'                   => '19880101201801' . $randomSuffix,
            'no_hp'                 => '081234567896',
            'skpd_id'               => $dinkes->id,
            'deskripsi'             => 'Puskesmas Kesambi',
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);

        // 3. Buat pemohon pada Deskripsi BUMD Mitra
        $pendaftarEksternal = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-EKS-' . $randomSuffix,
            'nama_lengkap'          => 'Analis BUMD Mitra ' . $randomSuffix,
            'nik'                   => '327401010192' . $randomSuffix,
            'nip'                   => '19920101202201' . $randomSuffix,
            'no_hp'                 => '081234567897',
            'skpd_id'               => $dinkes->id,
            'deskripsi'             => 'PT BUMD Air Minum',
            'status_memiliki_email' => false,
            'status'                => 'DISETUJUI',
        ]);

        // Test Endpoint Breakdown Utama: Mengembalikan breakdown penempatan deskripsi
        $response = $this->actingAs($this->admin)->getJson("/admin/capaian-skpd/{$dinkes->uuid}/breakdown");
        $response->assertStatus(200);

        $subUnitList = collect($response->json('breakdown_sub_kategori'))->pluck('sub_unit_kerja')->toArray();
        $this->assertContains('RSUD Gunung Jati', $subUnitList);
        $this->assertContains('Puskesmas Kesambi', $subUnitList);

        // Test Filter Deskripsi Puskesmas Kesambi
        $filterUnitResponse = $this->actingAs($this->admin)->getJson("/admin/capaian-skpd/{$dinkes->uuid}/breakdown?sub_unit=" . urlencode('Puskesmas Kesambi'));
        $filterUnitResponse->assertStatus(200);
        $pemohonUnit = $filterUnitResponse->json('pemohon_terbaru');
        $this->assertNotEmpty($pemohonUnit);
        foreach ($pemohonUnit as $p) {
            $this->assertEquals('Puskesmas Kesambi', $p['sub_unit_kerja']);
        }
    }

    public function test_dynamic_skpd_sub_kategori_drill_down_for_dinas_pendidikan_and_schools(): void
    {
        $disdik = Skpd::where('kode_skpd', 'DISDIK')
            ->orWhere('nama_skpd', 'ilike', '%pendidikan%')
            ->firstOrFail();

        $randomSuffix = random_int(1000, 9999);

        // Buat pemohon ASN guru di Sekolah Khusus (SLB / SKh)
        $guruSkh = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-SKH-' . $randomSuffix,
            'nama_lengkap'          => 'Ibu Guru Pembimbing Khusus ' . $randomSuffix,
            'nik'                   => '327401010195' . $randomSuffix,
            'nip'                   => '19870505201402' . $randomSuffix,
            'no_hp'                 => '081234567888',
            'skpd_id'               => $disdik->id,
            'deskripsi'             => 'SKh Negeri Kota Cirebon (Sekolah Khusus / SLB)',
            'status_memiliki_email' => false,
            'status'                => 'DISETUJUI',
        ]);

        // Buat pemohon ASN guru di SMP Negeri 1
        $guruSmp1 = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-SMP1-' . $randomSuffix,
            'nama_lengkap'          => 'Bapak Guru Matematika ' . $randomSuffix,
            'nik'                   => '327401010196' . $randomSuffix,
            'nip'                   => '19890606201603' . $randomSuffix,
            'no_hp'                 => '081234567889',
            'skpd_id'               => $disdik->id,
            'deskripsi'             => 'SMP Negeri 1 Kota Cirebon',
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);

        // Query breakdown Disdik
        $response = $this->actingAs($this->admin)->getJson("/admin/capaian-skpd/{$disdik->uuid}/breakdown");
        $response->assertStatus(200);

        $subUnitList = collect($response->json('breakdown_sub_kategori'))->pluck('sub_unit_kerja')->toArray();
        $this->assertContains('SKh Negeri Kota Cirebon (Sekolah Khusus / SLB)', $subUnitList);
        $this->assertContains('SMP Negeri 1 Kota Cirebon', $subUnitList);

        // Filter spesifik Sekolah Khusus
        $filterSkh = $this->actingAs($this->admin)->getJson("/admin/capaian-skpd/{$disdik->uuid}/breakdown?sub_unit=" . urlencode('SKh Negeri Kota Cirebon (Sekolah Khusus / SLB)'));
        $filterSkh->assertStatus(200);
        $pemohonSkh = $filterSkh->json('pemohon_terbaru');
        $this->assertNotEmpty($pemohonSkh);
        foreach ($pemohonSkh as $p) {
            $this->assertEquals('SKh Negeri Kota Cirebon (Sekolah Khusus / SLB)', $p['sub_unit_kerja']);
        }
    }

    public function test_dynamic_skpd_breakdown_csv_export_via_uuid(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/capaian-skpd/{$this->skpd->uuid}/export-breakdown");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));

        // Cek header kolom sub-kategori pada CSV
        $content = $response->streamedContent();
        $this->assertStringContainsString('Sub-Kategori ASN', $content);
        $this->assertStringContainsString('Sub-Unit / Unit Penempatan', $content);
    }

    public function test_h7_warning_lifecycle_and_badge_attributes(): void
    {
        $randomSuffix = random_int(1000, 9999);
        $pendaftaran = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-' . date('Ymd') . '-H7' . $randomSuffix,
            'nama_lengkap'          => 'Bambang Sudirman',
            'nik'                   => '327401010190' . $randomSuffix,
            'nip'                   => '19900101202012' . $randomSuffix,
            'no_hp'                 => '081234567891',
            'skpd_id'               => $this->skpd->id,
            'status_memiliki_email' => false,
            'status'                => 'DISETUJUI',
        ]);

        // Sertifikat H-6 (Mendekati kadaluarsa dalam 7 hari)
        $cert = SertifikatTte::create([
            'pendaftaran_id'     => $pendaftaran->id,
            'email_dinas'        => "bambang.sudirman{$randomSuffix}@cirebonkota.go.id",
            'tanggal_penerbitan' => Carbon::today()->subYears(2)->addDays(6)->toDateString(),
            'tanggal_kadaluarsa' => Carbon::today()->addDays(6)->toDateString(),
            'status_sertifikat'  => 'AKTIF',
            'verified_by_tahap2' => $this->admin->id,
        ]);

        $this->assertTrue($cert->isApproachingExpiry());

        // Jalankan Job scanner harian
        $job = new UpdateSertifikatStatusJob();
        $job->handle();

        $cert->refresh();
        $this->assertEquals('WARNING_H7', $cert->status_sertifikat);

        $badge = $cert->status_masa_berlaku_badge;
        $this->assertEquals('badge badge-warning-h7', $badge['class']);
        $this->assertStringContainsString('H-6', $badge['label']);
        $this->assertTrue($badge['is_urgent']);

        // Agregasi harian menghitung total_warning_h7
        $aggregator = app(RefreshDailyAggregatorAction::class);
        $stats = $aggregator->execute();
        $this->assertGreaterThanOrEqual(1, $stats->total_warning_h7);
    }

    public function test_master_skpd_routes_support_uuid(): void
    {
        // 1. Update SKPD via UUID
        $response = $this->actingAs($this->admin)->putJson("/admin/master/skpd/{$this->skpd->uuid}", [
            'kode_skpd' => $this->skpd->kode_skpd,
            'nama_skpd' => $this->skpd->nama_skpd . ' Updated',
            'singkatan' => $this->skpd->singkatan,
            'total_asn' => 120,
        ]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // 2. Toggle status SKPD via UUID
        $toggleResponse = $this->actingAs($this->admin)->postJson("/admin/master/skpd/{$this->skpd->uuid}/toggle");
        $toggleResponse->assertStatus(200);
        $this->assertTrue($toggleResponse->json('success'));
    }

    public function test_master_jabatan_golongan_routes_support_uuid(): void
    {
        $item = MasterJabatanGolongan::create([
            'kategori'   => 'JABATAN',
            'kode'       => 'JAB_UUID_TEST_' . random_int(100, 999),
            'nama_label' => 'Analis Sistem Informasi',
        ]);

        // 1. Update via UUID
        $updateResp = $this->actingAs($this->admin)->putJson("/admin/master/jabatan-golongan/{$item->uuid}", [
            'kode'       => $item->kode,
            'nama_label' => 'Analis Sistem Informasi Ahli Pertama',
        ]);
        $updateResp->assertStatus(200);
        $this->assertTrue($updateResp->json('success'));

        // 2. Delete via UUID
        $deleteResp = $this->actingAs($this->admin)->deleteJson("/admin/master/jabatan-golongan/{$item->uuid}");
        $deleteResp->assertStatus(200);
        $this->assertTrue($deleteResp->json('success'));
    }
}
