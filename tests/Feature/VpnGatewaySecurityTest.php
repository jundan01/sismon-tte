<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Test Suite Keamanan Perimeter VPN Gateway.
 * Memverifikasi isolasi rute admin dari IP publik non-VPN dan validasi subnet VPN.
 *
 * Ref: PRD.md §7.2 (FR-04a s/d FR-04c), SECURITY.md §4.3
 */
class VpnGatewaySecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('vpn.enabled', true);
        Config::set('vpn.allowed_subnets', ['127.0.0.1/32', '::1/128', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        Config::set('vpn.trusted_proxies', ['127.0.0.1/32', '::1/128', '172.16.0.0/12']);
        Config::set('vpn.block_response', 404);
        RateLimiter::clear('admin@cirebonkota.go.id|10.10.1.50');
        RateLimiter::clear('admin@cirebonkota.go.id|127.0.0.1');
    }

    public function test_public_user_cannot_access_gateway_login_from_unauthorized_public_ip(): void
    {
        // Simulasi request dari IP Publik (misal: 202.67.40.10)
        $response = $this->withServerVariables(['REMOTE_ADDR' => '202.67.40.10'])
            ->get('/gateway/login');

        // Harus ditolak dengan HTTP 404 (Stealth Mode)
        $response->assertStatus(404);
    }

    public function test_public_user_cannot_access_admin_dashboard_from_unauthorized_public_ip(): void
    {
        // Simulasi request ke panel admin dari IP Publik
        $response = $this->withServerVariables(['REMOTE_ADDR' => '202.67.40.10'])
            ->get('/admin/dashboard');

        // Harus ditolak dengan HTTP 404 (Stealth Mode)
        $response->assertStatus(404);
    }

    public function test_public_user_cannot_post_login_from_unauthorized_public_ip(): void
    {
        // Percobaan POST login dari IP publik
        $response = $this->withServerVariables(['REMOTE_ADDR' => '202.67.40.10'])
            ->postJson('/gateway/login', [
                'email'    => 'admin@cirebonkota.go.id',
                'password' => 'SuperAdmin2026!@#',
            ]);

        $response->assertStatus(404);
        $this->assertGuest();
    }

    public function test_authorized_vpn_subnet_can_access_gateway_login(): void
    {
        // Simulasi request dari Subnet VPN Diskominfo (10.10.1.50)
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.10.1.50'])
            ->get('/gateway/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk Superadmin');
        $response->assertSee('JARINGAN TERVERIFIKASI');
    }

    public function test_authorized_vpn_subnet_can_authenticate_successfully(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.10.1.50'])
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

    public function test_authorized_vpn_user_can_access_admin_dashboard_after_login(): void
    {
        $user = User::where('email', 'admin@cirebonkota.go.id')->first();

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '10.10.1.50'])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Transaksi');
        $response->assertSee('VPN DISKOMINFO TERVERIFIKASI');
    }

    public function test_vpn_protection_can_be_disabled_via_configuration(): void
    {
        Config::set('vpn.enabled', false);

        // Jika VPN dinonaktifkan, IP Publik diizinkan mengakses form login
        $response = $this->withServerVariables(['REMOTE_ADDR' => '202.67.40.10'])
            ->get('/gateway/login');

        $response->assertStatus(200);
    }

    /**
     * Uji Keamanan SEC-01: Penyerang dari IP publik tidak boleh membypass VPN
     * dengan menyuntikkan header CF-Connecting-IP palsu.
     */
    public function test_public_user_cannot_spoof_vpn_access_using_cf_connecting_ip_from_untrusted_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR'           => '202.67.40.10',
            'HTTP_CF_CONNECTING_IP' => '10.10.1.50',
        ])->get('/gateway/login');

        $response->assertStatus(404);
    }

    /**
     * Uji Keamanan SEC-01: Penyerang dari IP publik tidak boleh membypass VPN
     * dengan menyuntikkan header X-Forwarded-For palsu.
     */
    public function test_public_user_cannot_spoof_vpn_access_using_x_forwarded_for_from_untrusted_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR'          => '202.67.40.10',
            'HTTP_X_FORWARDED_FOR' => '10.10.1.50, 172.16.0.1',
        ])->get('/gateway/login');

        $response->assertStatus(404);
    }

    /**
     * Uji Keamanan SEC-01: Penyerang dari IP publik tidak boleh membypass VPN
     * dengan menyuntikkan header X-Real-IP palsu.
     */
    public function test_public_user_cannot_spoof_vpn_access_using_x_real_ip_from_untrusted_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR'     => '202.67.40.10',
            'HTTP_X_REAL_IP'  => '10.10.1.50',
        ])->get('/gateway/login');

        $response->assertStatus(404);
    }

    /**
     * Uji Fungsional SEC-01: Trusted Proxy (misal: 172.16.0.5) yang meneruskan
     * IP klien VPN resmi (10.10.1.50) harus diizinkan masuk (HTTP 200).
     */
    public function test_trusted_proxy_can_forward_authorized_vpn_client_ip(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR'           => '172.16.0.5',
            'HTTP_CF_CONNECTING_IP' => '10.10.1.50',
        ])->get('/gateway/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk Superadmin');
    }

    /**
     * Uji Fungsional SEC-01: Trusted Proxy yang meneruskan IP publik non-VPN
     * (202.67.40.10) harus ditolak dengan HTTP 404 (Stealth Mode).
     */
    public function test_trusted_proxy_forwarding_unauthorized_ip_is_blocked(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR'           => '172.16.0.5',
            'HTTP_CF_CONNECTING_IP' => '202.67.40.10',
        ])->get('/gateway/login');

        $response->assertStatus(404);
    }
}

