<?php

namespace Tests\Feature;

use App\Domain\Master\Models\MasterJabatanGolongan;
use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VerifikasiTahap1Test extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected PendaftaranTte $pendaftaran;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@cirebonkota.go.id')->first();
        $skpd = Skpd::first();

        $this->pendaftaran = PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5)),
            'nama_lengkap'             => 'Dewi Sartika, S.Kom',
            'nik'                      => '327401010190' . random_int(1000, 9999),
            'nip'                      => '19900101202012' . random_int(1000, 9999),
            'no_hp'                    => '081298765432',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => false,
            'email_usulan'             => 'dewi.sartika.199001012020122050@cirebonkota.go.id',
            'status'                   => 'MENUNGGU_VERIFIKASI',
            'is_email_recap_confirmed' => false,
        ]);
    }

    public function test_superadmin_can_view_verification_queue(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/admin/verifikasi');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'nomor_referensi', 'nama_lengkap', 'status'],
                ],
            ],
        ]);
    }

    public function test_superadmin_can_recap_official_email(): void
    {
        $emailDefinitif = 'dewi.sartika.199001012020122050@cirebonkota.go.id';

        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/rekap-email", [
            'email_final_terverifikasi' => $emailDefinitif,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                   => true,
            'email_final_terverifikasi' => $emailDefinitif,
            'is_email_recap_confirmed'  => true,
        ]);

        $this->pendaftaran->refresh();
        $this->assertEquals($emailDefinitif, $this->pendaftaran->email_final_terverifikasi);
        $this->assertTrue($this->pendaftaran->is_email_recap_confirmed);
    }

    public function test_superadmin_can_approve_registration(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/approve");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->pendaftaran->refresh();
        $this->assertEquals('DISETUJUI', $this->pendaftaran->status);
        $this->assertEquals($this->admin->id, $this->pendaftaran->verified_by);
        $this->assertNotNull($this->pendaftaran->verified_at);
    }

    public function test_superadmin_can_reject_registration_with_notes(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/reject", [
            'catatan_penolakan' => 'Data tidak sesuai data kepegawaian.',
        ]);

        $response->assertStatus(200);

        $this->pendaftaran->refresh();
        $this->assertEquals('DITOLAK', $this->pendaftaran->status);
        $this->assertEquals('Data tidak sesuai data kepegawaian.', $this->pendaftaran->catatan_penolakan);
    }

    public function test_superadmin_can_edit_and_recap_email_even_if_applicant_has_existing_email(): void
    {
        $skpd = Skpd::first();
        $pendaftaranExisting = PendaftaranTte::create([
            'nomor_referensi'          => 'TTE-' . date('Ymd') . '-EXT' . random_int(1000, 9999),
            'nama_lengkap'             => 'Bambang Kusuma',
            'nik'                      => '327401010190' . random_int(1000, 9999),
            'nip'                      => '19900101202012' . random_int(1000, 9999),
            'no_hp'                    => '081211223344',
            'skpd_id'                  => $skpd->id,
            'status_memiliki_email'    => true,
            'email_existing'           => 'bambang.lama@cirebonkota.go.id',
            'status'                   => 'MENUNGGU_VERIFIKASI',
            'is_email_recap_confirmed' => false,
        ]);

        // Admin mengoreksi / merubah email existing ke format standar baru
        $emailRevisi = 'bambang.kusuma.19900101202012@cirebonkota.go.id';

        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$pendaftaranExisting->nomor_referensi}/rekap-email", [
            'email_final_terverifikasi' => $emailRevisi,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                   => true,
            'email_final_terverifikasi' => $emailRevisi,
            'is_email_recap_confirmed'  => true,
        ]);

        $pendaftaranExisting->refresh();
        $this->assertEquals($emailRevisi, $pendaftaranExisting->email_final_terverifikasi);
        $this->assertTrue($pendaftaranExisting->is_email_recap_confirmed);
    }

    public function test_quick_collision_check_endpoint(): void
    {
        // 1. Cek email unik yang aman
        $responseSafe = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/check-collision", [
            'email' => 'dewi_sartika_99@cirebonkota.go.id',
        ]);

        $responseSafe->assertStatus(200);
        $responseSafe->assertJson([
            'success' => true,
            'data'    => [
                'is_available' => true,
            ],
        ]);

        // 2. Buat record lain yang menggunakan email tertentu
        $skpd = Skpd::first();
        PendaftaranTte::create([
            'nomor_referensi'           => 'TTE-20260828-EXIST',
            'nama_lengkap'              => 'Ratna Sari',
            'nik'                       => '3274010101901111',
            'nip'                       => '199001012020121111',
            'no_hp'                     => '081299887766',
            'skpd_id'                   => $skpd->id,
            'status_memiliki_email'     => true,
            'email_final_terverifikasi' => 'ratna_sari_19@cirebonkota.go.id',
            'status'                    => 'DISETUJUI',
            'is_email_recap_confirmed'  => true,
        ]);

        // 3. Cek email yang bertabrakan dengan record di atas
        $responseCollide = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/check-collision", [
            'email' => 'ratna_sari_19@cirebonkota.go.id',
        ]);

        $responseCollide->assertStatus(200);
        $responseCollide->assertJson([
            'success' => true,
            'data'    => [
                'is_available' => false,
                'reason'       => 'pendaftaran_tte',
            ],
        ]);
    }

    public function test_cannot_recap_or_approve_duplicate_email(): void
    {
        $skpd = Skpd::first();
        PendaftaranTte::create([
            'nomor_referensi'           => 'TTE-20260828-EXIST2',
            'nama_lengkap'              => 'Agus Pratama',
            'nik'                       => '3274010101902222',
            'nip'                       => '199001012020122222',
            'no_hp'                     => '081299887755',
            'skpd_id'                   => $skpd->id,
            'status_memiliki_email'     => true,
            'email_final_terverifikasi' => 'agus_pratama_19@cirebonkota.go.id',
            'status'                    => 'DISETUJUI',
            'is_email_recap_confirmed'  => true,
        ]);

        // Mencoba rekap dengan email yang sudah dipakai Agus Pratama
        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/rekap-email", [
            'email_final_terverifikasi' => 'agus_pratama_19@cirebonkota.go.id',
        ]);

        $response->assertStatus(422);
    }

    public function test_superadmin_can_update_applicant_jabatan_and_golongan(): void
    {
        $jabatan = MasterJabatanGolongan::jabatan()->first();
        $golongan = MasterJabatanGolongan::golongan()->first();

        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/jabatan-golongan", [
            'jabatan_id'  => $jabatan->id,
            'golongan_id' => $golongan->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'       => true,
            'message'       => 'Jabatan dan Golongan ASN berhasil diperbarui.',
            'jabatan_nama'  => $jabatan->nama_label,
            'golongan_nama' => $golongan->nama_label . ' (' . $golongan->kode . ')',
        ]);

        $this->pendaftaran->refresh();
        $this->assertEquals($jabatan->id, $this->pendaftaran->jabatan_id);
        $this->assertEquals($golongan->id, $this->pendaftaran->golongan_id);
    }

    public function test_superadmin_can_clear_applicant_jabatan_and_golongan(): void
    {
        // 1. Set awal jabatan dan golongan
        $jabatan = MasterJabatanGolongan::jabatan()->first();
        $golongan = MasterJabatanGolongan::golongan()->first();
        $this->pendaftaran->update([
            'jabatan_id'  => $jabatan->id,
            'golongan_id' => $golongan->id,
        ]);

        // 2. Kirim request untuk mengosongkan/menghapus jabatan dan golongan (null)
        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/jabatan-golongan", [
            'jabatan_id'  => null,
            'golongan_id' => null,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'       => true,
            'jabatan_nama'  => '-',
            'golongan_nama' => '-',
        ]);

        $this->pendaftaran->refresh();
        $this->assertNull($this->pendaftaran->jabatan_id);
        $this->assertNull($this->pendaftaran->golongan_id);
    }

    public function test_superadmin_cannot_set_invalid_jabatan_or_golongan(): void
    {
        $golongan = MasterJabatanGolongan::golongan()->first();

        // 1. Mengirimkan ID Golongan ke field jabatan_id (kategori salah)
        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/jabatan-golongan", [
            'jabatan_id'  => $golongan->id,
            'golongan_id' => $golongan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jabatan_id']);

        // 2. Mengirimkan ID non-existent (999999)
        $response2 = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/jabatan-golongan", [
            'jabatan_id'  => 999999,
            'golongan_id' => 999999,
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['jabatan_id', 'golongan_id']);
    }

    public function test_unauthenticated_or_non_superadmin_cannot_update_jabatan_and_golongan(): void
    {
        $response = $this->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/jabatan-golongan", [
            'jabatan_id'  => 1,
            'golongan_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_superadmin_recap_email_locks_domain_to_cirebonkota_go_id_when_only_prefix_given(): void
    {
        $prefix = 'dewi.sartika.prefixonly';
        $expectedEmail = 'dewi.sartika.prefixonly@cirebonkota.go.id';

        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/rekap-email", [
            'email_final_terverifikasi' => $prefix,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                   => true,
            'email_final_terverifikasi' => $expectedEmail,
            'is_email_recap_confirmed'  => true,
        ]);

        $this->pendaftaran->refresh();
        $this->assertEquals($expectedEmail, $this->pendaftaran->email_final_terverifikasi);
    }

    public function test_superadmin_recap_email_forces_cirebonkota_go_id_domain_even_if_other_domain_submitted(): void
    {
        $inputEmail = 'dewi.sartika.bypassed@externaldomain.com';
        $expectedEmail = 'dewi.sartika.bypassed@cirebonkota.go.id';

        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/rekap-email", [
            'email_final_terverifikasi' => $inputEmail,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                   => true,
            'email_final_terverifikasi' => $expectedEmail,
            'is_email_recap_confirmed'  => true,
        ]);

        $this->pendaftaran->refresh();
        $this->assertEquals($expectedEmail, $this->pendaftaran->email_final_terverifikasi);
    }

    public function test_quick_collision_check_uses_locked_cirebonkota_go_id_domain_with_prefix(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/verifikasi/{$this->pendaftaran->nomor_referensi}/check-collision", [
            'email' => 'unique_user_prefix_test',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'email'   => 'unique_user_prefix_test@cirebonkota.go.id',
            'data'    => [
                'is_available' => true,
            ],
        ]);
    }
}
