<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('vpn.enabled', true);
        Config::set('vpn.allowed_subnets', ['127.0.0.1/32', '::1/128', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        RateLimiter::clear('admin@cirebonkota.go.id|127.0.0.1');
        RateLimiter::clear('operator@cirebonkota.go.id|127.0.0.1');
        RateLimiter::clear(sha1('login_limit|127.0.0.1'));
        RateLimiter::clear(sha1('general|127.0.0.1'));
    }

    public function test_login_page_can_be_rendered_via_vpn(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/gateway/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk Superadmin');
        $response->assertSee('JARINGAN TERVERIFIKASI');
    }

    public function test_superadmin_can_authenticate_with_valid_credentials(): void
    {
        // Gunakan akun superadmin yang sudah di-seed
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/gateway/login', [
                'email'    => 'admin@cirebonkota.go.id',
                'password' => 'SuperAdmin2026!@#',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Autentikasi berhasil.',
        ]);

        $this->assertAuthenticated();
    }

    public function test_superadmin_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/gateway/login', [
                'email'    => 'admin@cirebonkota.go.id',
                'password' => 'PasswordSalah123!',
            ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Login Gagal',
        ]);

        $this->assertGuest();
    }

    public function test_login_lockout_after_multiple_failed_attempts(): void
    {
        // Kirim 5 kali password salah
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
                ->postJson('/gateway/login', [
                    'email'    => 'admin@cirebonkota.go.id',
                    'password' => 'PasswordSalah!',
                ]);
        }

        // Percobaan ke-6 harus dikunci (429 Too Many Requests)
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/gateway/login', [
                'email'    => 'admin@cirebonkota.go.id',
                'password' => 'SandiKuat!2026#TTE',
            ]);

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'success',
            'message',
            'retry_after',
        ]);
    }

    public function test_authenticated_superadmin_can_access_me_endpoint(): void
    {
        $user = User::where('email', 'admin@cirebonkota.go.id')->first();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/gateway/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'authenticated' => true,
            'user' => [
                'email' => 'admin@cirebonkota.go.id',
                'role'  => 'superadmin',
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_admin_endpoint(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/admin/dashboard');

        // Centralized exception handler mengembalikan 401 JSON untuk JSON request
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Sesi tidak valid atau telah berakhir. Silakan login kembali.',
        ]);
    }

    public function test_superadmin_can_logout_and_session_is_invalidated(): void
    {
        $user = User::where('email', 'admin@cirebonkota.go.id')->first();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/gateway/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Sesi berhasil diakhiri.',
        ]);

        $this->assertGuest();
    }

    public function test_security_headers_are_present_in_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }
}
