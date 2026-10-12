<?php

namespace Tests\Feature;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FrontendViewRenderTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected PendaftaranTte $pendaftaran;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Config::set('vpn.enabled', true);
        \Illuminate\Support\Facades\Config::set('vpn.allowed_subnets', ['127.0.0.1/32', '::1/128', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        $this->admin = User::where('email', 'admin@cirebonkota.go.id')->first();
        $skpd = Skpd::first();

        $randomSuffix = random_int(1000, 9999);
        $this->pendaftaran = PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-' . date('Ymd') . '-VW' . $randomSuffix,
            'nama_lengkap'             => 'Dra. Hj. Siti Nurhaliza, M.Pd',
            'nik'                      => '327401010190' . $randomSuffix,
            'nip'                      => '19900101202012' . $randomSuffix,
            'no_hp'                    => '081234567899',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'email_usulan'             => "siti.nurhaliza.19900101202012{$randomSuffix}@cirebonkota.go.id",
            'status'                   => 'MENUNGGU_VERIFIKASI',
            'is_email_recap_confirmed' => false,
        ]);
    }

    public function test_public_registration_portal_view_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Formulir Pendaftaran Sertifikat Elektronik (TTE)');
        $response->assertSee('Belum Memiliki Email Dinas');
        $response->assertSee('Sudah Memiliki Email Dinas');
        $response->assertSee('Pilih Unit Kerja / SKPD');
        $response->assertDontSee('local_captcha_answer');
        $response->assertDontSee('preview-email-usulan');
    }

    public function test_superadmin_dashboard_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Pemantauan TTE Kota Cirebon');
        $response->assertSee('Total ASN Terdaftar');
        $response->assertSee('Sertifikat TTE Aktif');
        $response->assertSee('Antrian Verifikasi Terdepan (FIFO)');
        $response->assertSee('/admin/verifikasi/TTE-');
        $response->assertDontSee('/admin/verifikasi/' . $this->pendaftaran->id . '"', false);

        // Validasi Widget Top SKPD (Mode default / persentase)
        $response->assertSee('Peringkat Kepatuhan Adopsi TTE per SKPD (Top 10)');
        $response->assertSee('Total ASN');
        $response->assertSee('Capaian Adopsi (%)');
        $response->assertSee('Kepatuhan (%)');
        $response->assertSee('Volume');

        // Validasi Sidebar Menu Capaian Adopsi SKPD
        $response->assertSee('Capaian Adopsi SKPD');

        // Validasi Mode Volume
        $volResponse = $this->actingAs($this->admin)->get('/admin/dashboard?sort=volume');
        $volResponse->assertStatus(200);
        $volResponse->assertSee('Distribusi Volume Permohonan TTE per SKPD (Top 10)');
    }

    public function test_superadmin_verifikasi_tahap1_views_render_successfully(): void
    {
        // 1. Index Antrian
        $indexResp = $this->actingAs($this->admin)->get('/admin/verifikasi');
        $indexResp->assertStatus(200);
        $indexResp->assertSee('Antrian Verifikasi Berkas Pemohon (Tahap 1)');

        // 2. Detail View via Nomor Referensi
        $showResp = $this->actingAs($this->admin)->get("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}");
        $showResp->assertStatus(200);
        $showResp->assertSee($this->pendaftaran->nomor_referensi);
        $showResp->assertSee($this->pendaftaran->nama_lengkap);
        $showResp->assertSee('Edit Jabatan & Golongan', false);
        $showResp->assertSee('modal-edit-jabatan-golongan', false);
        $showResp->assertSee('Kunci & Rekap', false);
        $showResp->assertSee('Setujui Permohonan (Tahap 1)', false);

        // 3. Test Canonical Redirect: Akses dengan ID angka dialihkan otomatis ke nomor_referensi
        $redirectResp = $this->actingAs($this->admin)->get("/admin/verifikasi/{$this->pendaftaran->id}");
        $redirectResp->assertRedirect(route('admin.verifikasi.show', $this->pendaftaran->nomor_referensi));
    }

    public function test_superadmin_penerbitan_tahap2_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/penerbitan');

        $response->assertStatus(200);
        $response->assertSee('Penerbitan Sertifikat Elektronik', false);
        $response->assertSee('Antrian Penerbitan Sertifikat (Tahap 2)', false);
        $response->assertSee('Daftar Sertifikat TTE Terbit', false);
    }

    public function test_superadmin_master_data_views_render_successfully(): void
    {
        // 1. Master SKPD
        $skpdResp = $this->actingAs($this->admin)->get('/admin/master/skpd');
        $skpdResp->assertStatus(200);
        $skpdResp->assertSee('Master Data Unit Kerja (SKPD)');

        // 2. Master Jabatan & Golongan
        $jabatanResp = $this->actingAs($this->admin)->get('/admin/master/jabatan-golongan');
        $jabatanResp->assertStatus(200);
        $jabatanResp->assertSee('Master Data Jabatan & Golongan ASN', false);

        // 3. Master Users & MFA
        $usersResp = $this->actingAs($this->admin)->get('/admin/master/users');
        $usersResp->assertStatus(200);
        $usersResp->assertSee('Manajemen Akun Internal & Keamanan IAM', false);
    }

    public function test_superadmin_capaian_skpd_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd');

        $response->assertStatus(200);
        $response->assertSee('Analitik Capaian Adopsi TTE per Unit Kerja (SKPD)');
        $response->assertSee('Rata-rata Capaian Kota', false);
        $response->assertSee('Ekspor CSV / Excel', false);
        $response->assertSee('Capaian Adopsi (%)', false);
    }

}
