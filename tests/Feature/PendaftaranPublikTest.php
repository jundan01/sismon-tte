<?php

namespace Tests\Feature;

use App\Domain\Master\Models\Skpd;
use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Services\Security\CryptoHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendaftaranPublikTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        RateLimiter::clear(sha1('pendaftaran_limit|127.0.0.1'));
    }

    protected function getCaptchaPayload(): array
    {
        $answer = 15;
        $timestamp = now()->timestamp;
        $signature = Crypt::encryptString("{$answer}|{$timestamp}");

        return [
            'local_captcha_answer'    => (string) $answer,
            'local_captcha_signature' => $signature,
        ];
    }

    public function test_public_can_submit_registration(): void
    {
        $skpd = Skpd::first();

        $randomNik = '3274010101' . random_int(100000, 999999);
        $randomNip = '19900101202012' . random_int(1000, 9999);

        $response = $this->postJson('/pendaftaran', array_merge([
            'nama_lengkap'          => 'Budi Santoso, S.ST',
            'nik'                   => $randomNik,
            'nip'                   => $randomNip,
            'no_hp'                 => '081234567890',
            'skpd_id'               => $skpd->id,
            'status_memiliki_email' => false,
        ], $this->getCaptchaPayload()));

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'status'  => 'MENUNGGU_VERIFIKASI',
        ]);

        // Verifikasi di database
        $nikEncrypted = CryptoHelper::encryptField($randomNik);
        $pendaftaran = PendaftaranTte::where('nik', $nikEncrypted)->first();

        $this->assertNotNull($pendaftaran);
        $this->assertEquals('Budi Santoso', $pendaftaran->nama_lengkap); // Murni tanpa gelar
        $this->assertEquals('budi_santoso_199@cirebonkota.go.id', $pendaftaran->email_usulan); // Format baru
        $this->assertEquals($randomNik, $pendaftaran->nik); // Dekripsi otomatis
        $this->assertEquals($randomNip, $pendaftaran->nip); // Dekripsi otomatis
        $this->assertStringStartsWith('TTE-', $pendaftaran->nomor_referensi);
    }

    public function test_duplicate_active_nik_returns_anti_enumeration_error(): void
    {
        $skpd = Skpd::first();

        $randomNik = '3274010102' . random_int(100000, 999999);
        $randomNip = '19900101202013' . random_int(1000, 9999);

        // Submit pendaftaran pertama
        $resp1 = $this->postJson('/pendaftaran', array_merge([
            'nama_lengkap'          => 'Andi Pratama',
            'nik'                   => $randomNik,
            'nip'                   => $randomNip,
            'no_hp'                 => '081234567891',
            'skpd_id'               => $skpd->id,
            'status_memiliki_email' => false,
        ], $this->getCaptchaPayload()));
        $resp1->assertStatus(201);

        RateLimiter::clear(sha1('pendaftaran_limit|127.0.0.1'));

        // Submit pendaftaran kedua dengan NIK yang sama
        $response = $this->postJson('/pendaftaran', array_merge([
            'nama_lengkap'          => 'Andi Pratama Duplicate',
            'nik'                   => $randomNik,
            'nip'                   => '19900101202014' . random_int(1000, 9999),
            'no_hp'                 => '081234567892',
            'skpd_id'               => $skpd->id,
            'status_memiliki_email' => false,
        ], $this->getCaptchaPayload()));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
        $response->assertJsonFragment([
            'nik' => ['NIK/NIP sudah terdata dalam antrian atau telah terverifikasi.'],
        ]);
    }

    public function test_resubmission_allowed_if_previous_status_was_ditolak(): void
    {
        $skpd = Skpd::first();

        $randomNik = '3274010103' . random_int(100000, 999999);
        $randomNip = '19900101202015' . random_int(1000, 9999);

        // 1. Submit awal
        $resp1 = $this->postJson('/pendaftaran', array_merge([
            'nama_lengkap'          => 'Rudi Hartono',
            'nik'                   => $randomNik,
            'nip'                   => $randomNip,
            'no_hp'                 => '081234567893',
            'skpd_id'               => $skpd->id,
            'status_memiliki_email' => false,
        ], $this->getCaptchaPayload()));
        $resp1->assertStatus(201);

        $nikEncrypted = CryptoHelper::encryptField($randomNik);
        $pendaftaran = PendaftaranTte::where('nik', $nikEncrypted)->first();
        $this->assertNotNull($pendaftaran);
        
        // 2. Set status menjadi DITOLAK
        $pendaftaran->update([
            'status' => 'DITOLAK',
            'catatan_penolakan' => 'Data tidak sesuai data kepegawaian.',
        ]);

        RateLimiter::clear(sha1('pendaftaran_limit|127.0.0.1'));

        // 3. Resubmit pendaftaran baru dengan NIK/NIP yang sama
        $response = $this->postJson('/pendaftaran', array_merge([
            'nama_lengkap'          => 'Rudi Hartono (Revisi)',
            'nik'                   => $randomNik,
            'nip'                   => $randomNip,
            'no_hp'                 => '081234567893',
            'skpd_id'               => $skpd->id,
            'status_memiliki_email' => false,
        ], $this->getCaptchaPayload()));

        $response->assertStatus(201);
        $this->assertEquals(2, PendaftaranTte::where('nik', $nikEncrypted)->count());
    }
}
