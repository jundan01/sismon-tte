<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StreamingExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_can_download_csv_stream_export(): void
    {
        $admin = User::where('email', 'admin@cirebonkota.go.id')->first();

        $response = $this->actingAs($admin)->get('/admin/export/csv');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('laporan_monitoring_tte_', $response->headers->get('Content-Disposition'));

        // Cek isi stream output
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Verifikasi UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Nama Lengkap ASN', $content);
        $this->assertStringContainsString('Unit Kerja (SKPD)', $content);
        $this->assertStringContainsString('Status Pendaftaran', $content);
    }
}
