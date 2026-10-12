<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware Proteksi Perimeter Jaringan VPN Gateway (Zero-Trust Security).
 * Memverifikasi bahwa request ke rute administratif berasal dari Subnet CIDR
 * VPN / Intranet resmi yang diizinkan.
 *
 * Ref: PRD.md §7.2 (FR-04a s/d FR-04c), SECURITY.md §4.3, ARCHITECTURE.md §1.2
 */
class EnsureVpnAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Jika proteksi VPN dinonaktifkan di environment tertentu (misal: testing lokal)
        if (!config('vpn.enabled', true)) {
            return $next($request);
        }

        // 1. Ekstraksi IP Peer Koneksi Langsung (Direct Remote Address)
        // Mencegah eksploitasi IP spoofing dengan memverifikasi apakah pengirim fisik
        // berasal dari daftar trusted proxies resmi (SEC-01).
        $directRemoteAddr = $request->server('REMOTE_ADDR') ?: '0.0.0.0';
        $trustedProxies = config('vpn.trusted_proxies', [
            '127.0.0.1/32',
            '::1/128',
            '172.16.0.0/12',
        ]);

        $isFromTrustedProxy = !empty($directRemoteAddr) && IpUtils::checkIp($directRemoteAddr, $trustedProxies);
        $clientOriginIp = null;

        // Hanya baca header forwarding JIKA koneksi langsung berasal dari trusted proxy
        if ($isFromTrustedProxy && ($cfIp = $request->header('CF-Connecting-IP'))) {
            $clientOriginIp = trim($cfIp);
        } elseif ($isFromTrustedProxy && ($xff = $request->header('X-Forwarded-For'))) {
            $parts = explode(',', $xff);
            $clientOriginIp = trim($parts[0]); // Ambil IP pertama (pengguna asli paling awal)
        } elseif ($isFromTrustedProxy && ($xRealIp = $request->header('X-Real-IP'))) {
            $clientOriginIp = trim($xRealIp);
        } else {
            // Klien terhubung langsung atau berasal dari IP di luar trusted proxies
            $clientOriginIp = $directRemoteAddr;
        }

        // Validasi format IP
        if (!$clientOriginIp || !filter_var($clientOriginIp, FILTER_VALIDATE_IP)) {
            $clientOriginIp = '0.0.0.0';
        }

        $allowedSubnets = config('vpn.allowed_subnets', [
            '127.0.0.1/32',
            '::1/128',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        // 2. Evaluasi HANYA IP Asli Pengguna terhadap Subnet yang Diizinkan
        $isIpAllowed = IpUtils::checkIp($clientOriginIp, $allowedSubnets);
        $detectedIp = $clientOriginIp;

        // 3. Evaluasi Header Secret jika gateway reverse proxy dikonfigurasi
        $gatewayHeader = config('vpn.gateway_header');
        $gatewaySecret = config('vpn.gateway_secret');
        $isHeaderValid = true;

        if (!empty($gatewayHeader) && !empty($gatewaySecret)) {
            $incomingHeader = (string) $request->header($gatewayHeader);
            $isHeaderValid = hash_equals((string) $gatewaySecret, $incomingHeader);
        }

        // 4. Jika IP atau Header lolos verifikasi -> Izinkan akses
        if ($isIpAllowed && $isHeaderValid) {
            return $next($request);
        }

        // 5. Pencatatan Log Pelanggaran Perimeter Keamanan
        if (config('vpn.log_violations', true)) {
            Log::warning('Percobaan akses rute administratif di luar perimeter VPN Gateway', [
                'detected_ip' => $detectedIp,
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'user_agent'  => $request->userAgent(),
                'allowed'     => false,
            ]);
        }

        // 6. Respon Pemblokiran (Default: HTTP 404 Stealth Mode)
        $statusCode = (int) config('vpn.block_response', 404);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $statusCode === 403 
                    ? 'Akses ditolak. Rute ini hanya dapat diakses melalui jaringan VPN resmi.'
                    : 'Sumber daya atau endpoint yang diminta tidak ditemukan.',
            ], $statusCode);
        }

        abort($statusCode, 'Sumber daya tidak ditemukan.');
    }
}
