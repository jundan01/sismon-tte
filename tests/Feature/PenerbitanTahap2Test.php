<?php

namespace Tests\Feature;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use App\Jobs\UpdateSertifikatStatusJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PenerbitanTahap2Test extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected PendaftaranTte $pendaftaranDisetujui;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@cirebonkota.go.id')->first();
        $skpd = Skpd::first();

        $randomSuffix = random_int(1000, 9999);
        $this->pendaftaranDisetujui = PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-' . date('Ymd') . '-AP' . $randomSuffix,
            'nama_lengkap'             => 'Ir. Hendra Gunawan',
            'nik'                      => '327401010190' . $randomSuffix,
            'nip'                      => '19900101202012' . $randomSuffix,
            'no_hp'                    => '081299887766',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'email_usulan'             => "hendra.gunawan.19900101202012{$randomSuffix}@cirebonkota.go.id",
            'email_final_terverifikasi'=> "hendra.gunawan.19900101202012{$randomSuffix}@cirebonkota.go.id",
            'is_email_recap_confirmed' => true,
            'status'                   => 'DISETUJUI',
            'verified_by'              => $this->admin->id,
            'verified_at'              => now(),
        ]);
    }

    public function test_superadmin_can_issue_certificate_with_automatic_2_year_expiry(): void
    {
        $tglTerbit = Carbon::today()->toDateString();
        $expectedExpiry = Carbon::today()->addYears(2)->toDateString();

        $response = $this->actingAs($this->admin)->postJson("/admin/penerbitan/{$this->pendaftaranDisetujui->nomor_referensi}", [
            'tanggal_penerbitan' => $tglTerbit,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'sertifikat' => [
                'status_sertifikat'  => 'AKTIF',
                'tanggal_penerbitan' => $tglTerbit,
                'tanggal_kadaluarsa' => $expectedExpiry,
            ],
        ]);

        $this->assertDatabaseHas('sertifikat_tte', [
            'pendaftaran_id'     => $this->pendaftaranDisetujui->id,
            'status_sertifikat'  => 'AKTIF',
            'tanggal_kadaluarsa' => $expectedExpiry,
        ]);
    }

    public function test_cannot_issue_certificate_for_unapproved_registration(): void
    {
        $skpd = Skpd::first();
        $pendaftaranMenunggu = PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-' . date('Ymd') . '-WAIT' . random_int(1000, 9999),
            'nama_lengkap'             => 'Eka Saputra',
            'nik'                      => '327401010190' . random_int(1000, 9999),
            'nip'                      => '19900101202012' . random_int(1000, 9999),
            'no_hp'                    => '081277665544',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'status'                   => 'MENUNGGU_VERIFIKASI',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/penerbitan/{$pendaftaranMenunggu->id}", [
            'tanggal_penerbitan' => Carbon::today()->toDateString(),
        ]);

        $response->assertStatus(422);
    }

    public function test_daily_scheduler_transitions_to_warning_h7_and_expired(): void
    {
        $uniqueEmail = 'hendra.gunawan.' . random_int(100000, 999999) . '@cirebonkota.go.id';

        // 1. Sertifikat H-5 (Masuk zona Warning H-7)
        $certWarning = SertifikatTte::create([
            'pendaftaran_id'     => $this->pendaftaranDisetujui->id,
            'email_dinas'        => $uniqueEmail,
            'tanggal_penerbitan' => Carbon::today()->subYears(2)->addDays(5)->toDateString(),
            'tanggal_kadaluarsa' => Carbon::today()->addDays(5)->toDateString(), // H-5
            'status_sertifikat'  => 'AKTIF',
            'verified_by_tahap2' => $this->admin->id,
        ]);

        // 2. Jalankan Job Scanner Harian
        $job = new UpdateSertifikatStatusJob();
        $job->handle();

        $certWarning->refresh();
        $this->assertEquals('WARNING_H7', $certWarning->status_sertifikat);

        // 3. Ubah tanggal kadaluarsa ke masa lalu -> Jalankan Job kembali
        $certWarning->update([
            'tanggal_kadaluarsa' => Carbon::today()->subDay()->toDateString(),
            'status_sertifikat'  => 'WARNING_H7',
        ]);

        $job->handle();

        $certWarning->refresh();
        $this->assertEquals('EXPIRED', $certWarning->status_sertifikat);
    }

    public function test_superadmin_can_issue_and_update_status_using_uuid(): void
    {
        $tglTerbit = Carbon::today()->toDateString();

        // 1. Issue via UUID pendaftaran
        $response = $this->actingAs($this->admin)->postJson("/admin/penerbitan/{$this->pendaftaranDisetujui->uuid}", [
            'tanggal_penerbitan' => $tglTerbit,
        ]);

        $response->assertStatus(201);
        $sertifikatData = $response->json('sertifikat');
        $this->assertNotEmpty($sertifikatData['uuid']);

        // 2. Update status via UUID sertifikat to WARNING_H7
        $updateResp = $this->actingAs($this->admin)->postJson("/admin/penerbitan/sertifikat/{$sertifikatData['uuid']}/status", [
            'status_sertifikat' => 'WARNING_H7',
        ]);

        $updateResp->assertStatus(200);
        $updateResp->assertJson([
            'success' => true,
            'sertifikat' => [
                'status_sertifikat' => 'WARNING_H7',
            ],
        ]);
    }
}
