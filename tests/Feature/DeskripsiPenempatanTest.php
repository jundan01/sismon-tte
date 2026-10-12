<?php

namespace Tests\Feature;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeskripsiPenempatanTest extends TestCase
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

    /**
     * Test formulir pendaftaran publik menerima dan menyimpan field deskripsi secara manual,
     * serta tidak lagi mewajibkan atau memproses field sub kategori / sub unit.
     */
    public function test_asn_can_submit_registration_with_manual_deskripsi(): void
    {
        $randomSuffix = random_int(1000, 9999);
        $payload = [
            'nama_lengkap'          => 'Bambang Sudarmanto, S.Pd',
            'nik'                   => '327401010190' . $randomSuffix,
            'nip'                   => '19910101202012' . $randomSuffix,
            'no_hp'                 => '081234567890',
            'skpd_id'               => $this->skpd->id,
            'deskripsi'             => 'SDN 1 Kejaksan (Guru Kelas IV)',
            'status_memiliki_email' => 0,
        ];

        $response = $this->postJson('/pendaftaran', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pendaftaran_tte', [
            'nama_lengkap' => 'Bambang Sudarmanto',
            'deskripsi'    => 'SDN 1 Kejaksan (Guru Kelas IV)',
            'status'       => 'MENUNGGU_VERIFIKASI',
        ]);
    }

    /**
     * Test dashboard admin menampilkan informasi deskripsi pada widget antrian verifikasi pertama.
     */
    public function test_admin_dashboard_displays_deskripsi_in_first_verification_queue(): void
    {
        $randomSuffix = random_int(1000, 9999);
        $deskripsiUnik = 'Puskesmas Kesambi - Poli Gigi ' . $randomSuffix;

        $pendaftaran = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-DESK-' . $randomSuffix,
            'nama_lengkap'          => 'drg. Maya Anggraini ' . $randomSuffix,
            'nik'                   => '327401010191' . $randomSuffix,
            'nip'                   => '19890101201901' . $randomSuffix,
            'no_hp'                 => '081234567891',
            'skpd_id'               => $this->skpd->id,
            'deskripsi'             => $deskripsiUnik,
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);

        \Illuminate\Support\Facades\DB::table('pendaftaran_tte')
            ->where('id', $pendaftaran->id)
            ->update(['created_at' => now()->subYears(5)]);

        // Cek tampil di dashboard umum (FIFO)
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee($pendaftaran->nomor_referensi);
        $response->assertSee($deskripsiUnik);

        // Cek fitur pencarian deskripsi langsung pada widget dashboard admin
        $searchResponse = $this->actingAs($this->admin)->get('/admin?q=' . urlencode($deskripsiUnik));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee($pendaftaran->nomor_referensi);
        $searchResponse->assertSee($deskripsiUnik);
    }

    /**
     * Test fitur pencarian antrian verifikasi berdasarkan kata kunci deskripsi.
     */
    public function test_admin_can_search_verification_queue_by_deskripsi(): void
    {
        $randomSuffix = random_int(1000, 9999);
        $keywordSpesifik = 'RSUD_Gunung_Jati_ICU_' . $randomSuffix;

        $target = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-TGT-' . $randomSuffix,
            'nama_lengkap'          => 'Perawat Khusus ' . $randomSuffix,
            'nik'                   => '327401010192' . $randomSuffix,
            'nip'                   => '19930101202101' . $randomSuffix,
            'no_hp'                 => '081234567892',
            'skpd_id'               => $this->skpd->id,
            'deskripsi'             => $keywordSpesifik,
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);

        $other = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-OTH-' . $randomSuffix,
            'nama_lengkap'          => 'Staf Kantor Induk ' . $randomSuffix,
            'nik'                   => '327401010193' . $randomSuffix,
            'nip'                   => '19940101202201' . $randomSuffix,
            'no_hp'                 => '081234567893',
            'skpd_id'               => $this->skpd->id,
            'deskripsi'             => 'Kantor Sekretariat Utama',
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);

        // Cari via parameter q pada index verifikasi
        $searchResponse = $this->actingAs($this->admin)->getJson("/admin/verifikasi?q={$keywordSpesifik}");

        $searchResponse->assertStatus(200);
        $pendaftarDitemukan = collect($searchResponse->json('data.data'))->pluck('nomor_referensi')->toArray();

        $this->assertContains($target->nomor_referensi, $pendaftarDitemukan);
        $this->assertNotContains($other->nomor_referensi, $pendaftarDitemukan);

        // Cari via view HTML
        $htmlResponse = $this->actingAs($this->admin)->get("/admin/verifikasi?q={$keywordSpesifik}");
        $htmlResponse->assertStatus(200);
        $htmlResponse->assertSee($target->nomor_referensi);
        $htmlResponse->assertSee($keywordSpesifik);
    }

    /**
     * Test halaman detail verifikasi pertama menampilkan isi deskripsi penempatan kerja.
     */
    public function test_verification_detail_displays_deskripsi_information(): void
    {
        $randomSuffix = random_int(1000, 9999);
        $deskripsiTugas = 'UPTD Pengujian Kendaraan Bermotor Dishub';

        $pendaftaran = PendaftaranTte::create([
            'nomor_referensi'       => 'TTE-DET-' . $randomSuffix,
            'nama_lengkap'          => 'Petugas Penguji ' . $randomSuffix,
            'nik'                   => '327401010194' . $randomSuffix,
            'nip'                   => '19950101202301' . $randomSuffix,
            'no_hp'                 => '081234567894',
            'skpd_id'               => $this->skpd->id,
            'deskripsi'             => $deskripsiTugas,
            'status_memiliki_email' => false,
            'status'                => 'MENUNGGU_VERIFIKASI',
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/verifikasi/{$pendaftaran->nomor_referensi}");

        $response->assertStatus(200);
        $response->assertSee('Deskripsi Penempatan');
        $response->assertSee($deskripsiTugas);
    }
}
