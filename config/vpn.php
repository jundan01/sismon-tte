<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VPN Gateway Protection Status
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah pemeriksaan perimeter jaringan VPN Gateway aktif.
    | Jika true, hanya klien dengan IP dalam subnet yang diizinkan (atau
    | membawa header gateway yang valid) yang dapat mengakses rute admin.
    |
    */

    'enabled' => env('VPN_GATEWAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed VPN & Internal IP Subnets (CIDR Notation)
    |--------------------------------------------------------------------------
    |
    | Daftar CIDR Subnet IP yang diizinkan untuk mengakses portal administrasi.
    | Standar RFC 1918 Private Networks & Loopback:
    | - 127.0.0.1/32  (Localhost IPv4)
    | - ::1/128       (Localhost IPv6)
    | - 10.0.0.0/8    (VPN Diskominfo / Intranet Pemerintahan)
    | - 172.16.0.0/12 (Docker bridge & Private Network)
    | - 192.168.0.0/16(LAN Kantor / Dinas)
    |
    */

    'allowed_subnets' => (function () {
        $raw = env('VPN_ALLOWED_SUBNETS');
        if (file_exists(base_path('.env'))) {
            $envContent = file_get_contents(base_path('.env'));
            if (preg_match('/^VPN_ALLOWED_SUBNETS=(.*)$/m', $envContent, $matches)) {
                $raw = trim(trim($matches[1]), "\"'");
            }
        }
        $raw = $raw ?: '127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16';
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    })(),

    /*
    |--------------------------------------------------------------------------
    | Trusted Reverse Proxies (Anti-Spoofing Whitelist)
    |--------------------------------------------------------------------------
    |
    | Subnet atau IP reverse proxy terpercaya yang diizinkan meneruskan header
    | identitas klien asli (CF-Connecting-IP, X-Forwarded-For, X-Real-IP).
    | Request yang berasal langsung dari IP di luar daftar ini tidak akan
    | mengevaluasi header forwarding untuk mencegah IP Spoofing (SEC-01).
    |
    */

    'trusted_proxies' => (function () {
        $raw = env('VPN_TRUSTED_PROXIES');
        if (file_exists(base_path('.env'))) {
            $envContent = file_get_contents(base_path('.env'));
            if (preg_match('/^VPN_TRUSTED_PROXIES=(.*)$/m', $envContent, $matches)) {
                $raw = trim(trim($matches[1]), "\"'");
            }
        }
        $raw = $raw ?: '127.0.0.1/32,::1/128,172.16.0.0/12';
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    })(),

    /*
    |--------------------------------------------------------------------------
    | Optional Gateway Proxy Header & Secret
    |--------------------------------------------------------------------------
    |
    | Jika sistem berada di belakang VPN Gateway Reverse Proxy terpercaya,
    | header dan token rahasia ini dapat digunakan untuk validasi tambahan.
    |
    */

    'gateway_header' => env('VPN_GATEWAY_HEADER', null),

    'gateway_secret' => env('VPN_GATEWAY_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Response Mode on Unauthorized Access
    |--------------------------------------------------------------------------
    |
    | Kode status HTTP yang dikembalikan saat akses ditolak:
    | - 404 (Default / Stealth Mode): Menyembunyikan eksistensi portal admin.
    | - 403 (Forbidden): Menolak secara eksplisit dengan pesan akses terlarang.
    |
    */

    'block_response' => (int) env('VPN_BLOCK_RESPONSE', 404),

    /*
    |--------------------------------------------------------------------------
    | Security Audit Logging on Perimeter Violation
    |--------------------------------------------------------------------------
    |
    | Jika true, setiap upaya akses tidak sah dari IP non-VPN akan dicatat ke
    | file log keamanan Laravel dengan detail IP, URL, dan User Agent.
    |
    */

    'log_violations' => env('VPN_LOG_VIOLATIONS', true),

];
