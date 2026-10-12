<?php

namespace Tests\Feature;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CapaianSkpdTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Config::set('vpn.enabled', true);
        \Illuminate\Support\Facades\Config::set('vpn.allowed_subnets', ['127.0.0.1/32', '::1/128', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        $this->admin = User::where('email', 'admin@cirebonkota.go.id')->first();
    }

    public function test_capaian_skpd_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd');

        $response->assertStatus(200);
        $response->assertSee('Analitik Capaian Adopsi TTE per Unit Kerja (SKPD)');
        $response->assertSee('Rata-rata Capaian Kota', false);
        $response->assertSee('Total SKPD Aktif', false);
        $response->assertSee('Total ASN Terdata', false);
        $response->assertSee('Total Pemohon TTE', false);
        $response->assertSee('Capaian Adopsi (%)', false);
        $response->assertSee('Ekspor CSV / Excel', false);
    }

    public function test_capaian_skpd_shows_kpi_aggregates(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd');

        $response->assertStatus(200);
        // KPI cards
        $response->assertSee('Optimal', false);
        $response->assertSee('Progres', false);
        $response->assertSee('Perlu Percepatan', false);
        $response->assertSee('Belum Diset Target', false);
    }

    public function test_capaian_skpd_search_filter_works(): void
    {
        $skpd = Skpd::where('is_active', true)->first();
        $searchTerm = substr($skpd->nama_skpd, 0, 5);

        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd?q=' . urlencode($searchTerm));

        $response->assertStatus(200);
        $response->assertSee($skpd->nama_skpd, false);
    }

    public function test_capaian_skpd_category_filter_works(): void
    {
        // Test each category filter
        foreach (['optimal', 'progres', 'percepatan', 'belum_diset', 'semua'] as $kategori) {
            $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd?kategori=' . $kategori);
            $response->assertStatus(200);
        }
    }

    public function test_capaian_skpd_sorting_works(): void
    {
        $sorts = ['persentase_desc', 'persentase_asc', 'volume_desc', 'volume_asc', 'total_asn_desc', 'nama_skpd_asc'];

        foreach ($sorts as $sort) {
            $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd?sort=' . $sort);
            $response->assertStatus(200);
        }
    }

    public function test_capaian_skpd_shows_all_active_skpd(): void
    {
        $totalActiveSkpd = Skpd::where('is_active', true)->count();

        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd');
        $response->assertStatus(200);

        // The page should show total count (pagination shows total)
        $response->assertSee((string) $totalActiveSkpd, false);
    }

    public function test_capaian_skpd_export_csv_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd/export');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('capaian_adopsi_tte_skpd_', $response->headers->get('Content-Disposition'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Verify UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Verify header columns exist
        $this->assertStringContainsString('Kode SKPD', $content);
        $this->assertStringContainsString('Nama Unit Kerja (SKPD)', $content);
        $this->assertStringContainsString('Target Pegawai (ASN)', $content);
        $this->assertStringContainsString('Persentase Capaian (%)', $content);
        $this->assertStringContainsString('Status Kategori', $content);
        $this->assertStringContainsString('Tanggal Data Diekspor', $content);
    }

    public function test_capaian_skpd_export_csv_respects_filters(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/capaian-skpd/export?kategori=optimal&sort=persentase_desc');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function test_unauthenticated_user_cannot_access_capaian_skpd(): void
    {
        $response = $this->get('/admin/capaian-skpd');
        $response->assertRedirect();
    }

    public function test_unauthenticated_user_cannot_export_capaian_skpd(): void
    {
        $response = $this->get('/admin/capaian-skpd/export');
        $response->assertRedirect();
    }

    public function test_dashboard_has_link_to_capaian_skpd(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Lihat Seluruh SKPD', false);
        $response->assertSee(route('admin.capaian-skpd.index'), false);
    }

    public function test_sidebar_has_capaian_skpd_menu(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Capaian Adopsi SKPD', false);
    }
}
