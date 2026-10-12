<?php

namespace Tests\Feature;

use App\Domain\Master\Models\MasterJabatanGolongan;
use App\Domain\Master\Models\Skpd;
use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MasterDataAndMfaTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@cirebonkota.go.id')->first();
    }

    public function test_can_manage_master_skpd(): void
    {
        $code1 = 'SKPD-' . random_int(10000, 99999);
        $code2 = 'SKPD-' . random_int(10000, 99999);

        // 1. Tambah SKPD baru
        $response = $this->actingAs($this->admin)->postJson('/admin/master/skpd', [
            'kode_skpd' => $code1,
            'nama_skpd' => 'Dinas Komunikasi Test Cirebon',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $skpdId = $response->json('data.id');

        // 2. Update SKPD
        $updateResp = $this->actingAs($this->admin)->putJson("/admin/master/skpd/{$skpdId}", [
            'kode_skpd' => $code2,
            'nama_skpd' => 'Dinas Komunikasi Test Cirebon Updated',
            'is_active' => true,
        ]);

        $updateResp->assertStatus(200);
        $this->assertDatabaseHas('skpd', ['kode_skpd' => $code2]);

        // 3. Toggle Status
        $toggleResp = $this->actingAs($this->admin)->postJson("/admin/master/skpd/{$skpdId}/toggle");
        $toggleResp->assertStatus(200);
        $this->assertFalse($toggleResp->json('is_active'));
    }

    public function test_can_manage_master_jabatan_golongan(): void
    {
        $jabCode = 'JAB-' . random_int(10000, 99999);

        // 1. Tambah Jabatan baru
        $response = $this->actingAs($this->admin)->postJson('/admin/master/jabatan-golongan', [
            'kategori'   => 'JABATAN',
            'kode'       => $jabCode,
            'nama_label' => 'Kepala Balai Persandian',
        ]);

        $response->assertStatus(201);
        $jabatanId = $response->json('data.id');

        // 2. Hapus Master Data yang belum digunakan
        $delResp = $this->actingAs($this->admin)->deleteJson("/admin/master/jabatan-golongan/{$jabatanId}");
        $delResp->assertStatus(200);
        $this->assertDatabaseMissing('master_jabatan_golongan', ['id' => $jabatanId]);
    }

    public function test_can_setup_and_confirm_totp_mfa(): void
    {
        // 1. Setup MFA
        $setupResp = $this->actingAs($this->admin)->getJson('/admin/mfa/setup');
        $setupResp->assertStatus(200);
        $setupResp->assertJsonStructure(['success', 'secret', 'qr_uri', 'recovery_codes']);

        $secret = $setupResp->json('secret');

        // 2. Hitung kode OTP valid menggunakan TotpService
        /** @var TotpService $totp */
        $totp = app(TotpService::class);
        $validCode = $totp->calculateCode($secret, (int)floor(time() / 30));

        // 3. Konfirmasi MFA
        $confirmResp = $this->actingAs($this->admin)->postJson('/admin/mfa/confirm', [
            'code' => $validCode,
        ]);

        $confirmResp->assertStatus(200);
        $confirmResp->assertJson(['success' => true]);

        $this->admin->refresh();
        $this->assertTrue($this->admin->is_mfa_enabled);
        $this->assertNotNull($this->admin->mfa_secret);
        $this->assertNotNull($this->admin->mfa_recovery_codes);

        // 4. Nonaktifkan MFA dengan kata sandi yang valid
        $disableResp = $this->actingAs($this->admin)->postJson("/admin/master/users/{$this->admin->id}/disable-mfa", [
            'current_password' => 'SuperAdmin2026!@#',
        ]);
        $disableResp->assertStatus(200);
        $disableResp->assertJson(['success' => true]);

        $this->admin->refresh();
        $this->assertFalse($this->admin->is_mfa_enabled);
        $this->assertNull($this->admin->mfa_secret);
    }
}
