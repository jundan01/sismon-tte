# DOKUMEN CATATAN PERUBAHAN SISTEM (CHANGELOG & CODE COMPARISON)
## UPGRADE KE LARAVEL 13 & ISOLASI JALUR ADMIN BERBASIS VPN GATEWAY

**Proyek:** Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas (`@cirebonkota.go.id`)  
**Instansi:** Dinas Komunikasi dan Informatika / Bidang Persandian Kota Cirebon  
**Versi Rilis:** 3.1.0-PROD (Enterprise Upgrade & Zero-Trust VPN Gateway)  
**Tanggal Pembaruan:** 19 Agustus 2026  
**Dokumen:** `perubahan.md`  
**Status:** DOKUMENTASI RESMI REVISI SPESIFIKASI, ARSITEKTUR, DAN KOMPARASI KODE SUMBER  

---

## 1. Ringkasan Eksekutif Pembaruan

Pembaruan Versi **3.1.0-PROD** difokuskan pada penguatan fondasi arsitektur sistem skala nasional dengan 2 (dua) pilar utama:
1. **Upgrade Stack Framework ke Laravel 13:** Menyelaraskan seluruh spesifikasi modul, runtime PHP 8.3, struktur container, dan konfigurasi framework dari Laravel 12 ke Laravel 13.
2. **Pemisahan Jalur Masuk Admin Berbasis VPN Gateway (Zero-Trust & Stealth Defense):** Mengeliminasi risiko *administrative surface exposure* dengan menghapus rute login admin publik (`/login`). Seluruh autentikasi dan panel kendali Superadmin dipindahkan ke jalur terisolasi (`/gateway/login` dan `/admin/*`) yang di-guard oleh subnet IP VPN (`EnsureVpnAccess`). Setiap percobaan akses dari internet publik otomatis menghasilkan respon **HTTP 404 Not Found (Stealth Mode)**.

---

## 2. Matriks Pembaruan Berkas Spesifikasi (`docs/`)

| No | Berkas Dokumentasi | Versi Baru | Ringkasan Pokok Perubahan |
| :-: | :--- | :-: | :--- |
| 1 | [`docs/PRD.md`](file:///f:/sismon-tte-testing-V4/docs/PRD.md) | **3.1.0-PROD** | • Penambahan Kebutuhan Fungsional FR-04a s/d FR-04c (Isolasi rute admin, inspeksi subnet VPN, respon Stealth 404).<br>• Penyesuaian persona Superadmin (wajib melalui VPN Diskominfo).<br>• Update Traceability Matrix dan kontrol versi. |
| 2 | [`docs/ARCHITECTURE.md`](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md) | **3.1.0-PROD** | • Pembaruan Diagram Topologi Jaringan (Jalur Publik vs Jalur Internal VPN Gateway).<br>• Penambahan struktur teknis: `EnsureVpnAccess.php` dan `config/vpn.php`.<br>• Penegasan prinsip isolasi jaringan 3-lapis. |
| 3 | [`docs/SECURITY.md`](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md) | **3.1.0-PROD** | • Penambahan mitigasi ancaman *Administrative Attack Surface* pada Threat Matrix.<br>• Penambahan Layer 0–1 Network Perimeter (Subnet CIDR Whitelisting & Stealth 404).<br>• Spesifikasi audit logging otomatis untuk pelanggaran perimeter VPN.<br>• Checklist hardening sebelum go-live diperbarui. |
| 4 | [`docs/DESIGN.md`](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md) | **3.1.0-PROD** | • Penambahan token komponen visual `vpn-status-pill` pada header panel admin.<br>• Pemisahan arsitektur antarmuka: Form publik mandiri vs form login VPN di `/gateway/login`. |
| 5 | [`docs/SCHEMA.md`](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) | **3.1.0-PROD** | • Penyelarasan model cast dan dependensi runtime Laravel 13.<br>• Penegasan pencatatan jejak IP koneksi VPN pada atribut `users.last_login_ip`.<br>• Validasi konsistensi 9 skema DDL PostgreSQL 16 Enterprise. |
| 6 | [`docs/OPERATIONAL_GUIDE.md`](file:///f:/sismon-tte-testing-V4/docs/OPERATIONAL_GUIDE.md) | **3.1.0-PROD** | • Diagram alur deployment & operasional baru.<br>• Penambahan SOP Akses Superadmin via VPN Gateway.<br>• Panduan konfigurasi `.env` (`VPN_GATEWAY_ENABLED`, `VPN_ALLOWED_SUBNETS`).<br>• Panduan penanganan kendala akses (Troubleshooting 404/403). |
| 7 | [`rencana_upgrade-v3.md`](file:///f:/sismon-tte-testing-V4/rencana_upgrade-v3.md) | **3.1.0-PROD** | • Rencana kerja teknis bertahap (Langkah 1 s/d 8) untuk eksekusi kode program. |

---

## 3. Rincian Perubahan Dokumen Spesifikasi

### 3.1 [`docs/PRD.md`](file:///f:/sismon-tte-testing-V4/docs/PRD.md)
- **Header & Versi:** Ditingkatkan menjadi Versi `3.1.0-PROD` tertanggal 19 Agustus 2026.
- **Catatan Pembaruan Rilis:** Ditambahkan butir 2 mengenai arsitektur Zero-Trust VPN Gateway.
- **Tabel Masalah & Solusi (§2):** Ditambahkan baris mitigasi *Administrative Surface Exposure* melalui pemisahan jalur dengan respon Stealth 404.
- **Kebutuhan Fungsional Baru (§7.2):**
  - **FR-04a:** Penempatan rute autentikasi admin di `/gateway/login` dan seluruh rute manajemen di `/admin/*`.
  - **FR-04b:** Validasi alamat IP terhadap Subnet CIDR resmi (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1/32`) melalui middleware `EnsureVpnAccess`.
  - **FR-04c:** Pengembalian respon HTTP 404 Not Found untuk segala request dari IP publik non-VPN.
- **Kebutuhan Non-Fungsional (§8):** Ditambahkan kriteria keamanan segmentasi jaringan Zero-Trust.
- **Traceability Matrix (§9):** Pemetaan kebutuhan VPN Gateway terhadap berkas arsitektur, skema, desain, dan keamanan.

### 3.2 [`docs/ARCHITECTURE.md`](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md)
- **Topologi Jaringan (§1.1):** Diagram ASCII diperbarui memisahkan dua aliran trafik:
  1. *Jalur Publik (Internet):* ASN Pemohon -> Edge Nginx -> Form Pendaftaran (`/` & `/pendaftaran`).
  2. *Jalur Internal (Diskominfo VPN):* Superadmin -> Secure VPN Gateway (Subnet 10.x/172.x) -> Edge Nginx (IP CIDR Whitelist) -> Laravel 13 Runtime (`/gateway/login` & `/admin/*`).
- **Prinsip Isolasi (§1.2):** Ditambahkan klausul *Isolasi Jalur Admin (VPN Gateway & Stealth Defense)*.
- **Pilihan Teknologi (§3):** Ditambahkan komponen *VPN Gateway + `EnsureVpnAccess` Middleware*.
- **Struktur Teknis Clean Domain (§8):** Ditambahkan berkas `app/Http/Middleware/EnsureVpnAccess.php` dan `config/vpn.php`.
- **Perbaikan Path:** Seluruh tautan absolut disesuaikan ke `f:/sismon-tte-testing-V4`.

### 3.3 [`docs/SECURITY.md`](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md)
- **Filosofi Keamanan (§1):** Dimasukkan prinsip *Zero-Trust Administrative Perimeter*.
- **Threat Matrix (§2):** Ditambahkan vektor ancaman *Administrative Attack Surface* (Port scanning, bot brute force) dengan mitigasi isolasi VPN Gateway & Stealth 404.
- **Diagram Defense-in-Depth (§3):** Layer 0, Layer 1, dan Layer 2 diperbarui dengan inspeksi subnet CIDR dan `EnsureVpnAccess`.
- **Lapisan 0–1 Network & VPN Gateway (§4.3):**
  - Konfigurasi subnet CIDR resmi.
  - Logika bitwise matching IP klien.
  - Mekanisme respon Stealth Mode 404.
  - Format logging otomatis pelanggaran perimeter (`Log::warning(...)`).
- **Lapisan 4 IAM (§6.2):** Penegasan *Dual-Factor Access Requirement* bagi Superadmin: (1) Jaringan VPN, (2) Kata Sandi Kuat + Lockout, (3) RFC 6238 TOTP MFA.
- **Checklist Hardening (§10):** Ditambahkan verifikasi jalur login admin terisolasi dan keaktifan middleware VPN.

### 3.4 [`docs/DESIGN.md`](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md)
- **Komponen Baru:** Ditambahkan token komponen `vpn-status-pill` (warna aksen `semantic-success` dengan padding 4px 10px dan border radius pill).
- **Karakteristik Desain (§1):** Ditambahkan butir 4 mengenai *Visibilitas Status Keamanan Jaringan*.
- **Tata Letak Panel Superadmin (§5.2):** Penambahan visual badge status `[● VPN DISKOMINFO TERVERIFIKASI]` pada Top Bar Admin.
- **Spesifikasi Komponen (§6.3):** Ditambahkan rincian tampilan widget indikator status VPN Gateway.

### 3.5 [`docs/SCHEMA.md`](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md)
- **Standar Skema:** Penyelarasan untuk spesifikasi Laravel 13 dan PostgreSQL 16 Enterprise.
- **Entitas `users` (§7.3):** Penegasan peran kolom `last_login_ip` untuk mencatat alamat IP VPN verifikator.
- **Pembersihan Path:** Seluruh rujukan path direktori dan dokumen internal diperbarui ke `f:/sismon-tte-testing-V4`.

### 3.6 [`docs/OPERATIONAL_GUIDE.md`](file:///f:/sismon-tte-testing-V4/docs/OPERATIONAL_GUIDE.md)
- **Diagram Alur Produksi (§1):** Diperbarui dengan visual Mermaid yang memisahkan akses Browser ASN Publik dengan Superadmin via VPN Gateway.
- **SOP Administrasi VPN Gateway (§3):**
  - Prosedur aktivasi koneksi VPN sebelum mengakses sistem.
  - URL resmi login internal: `https://tte.cirebonkota.go.id/gateway/login`.
  - Prosedur investigasi dan penanganan kendala akses 404/403 (Troubleshooting).
- **Konfigurasi Environment (.env):** Ditambahkan variabel `VPN_GATEWAY_ENABLED`, `VPN_ALLOWED_SUBNETS`, dan `VPN_BLOCK_RESPONSE`.
- **Perintah Crontab & Supervisor:** Disesuaikan dengan path kerja `f:/sismon-tte-testing-V4`.

---

## 4. Matriks Ringkasan Berkas Kode Program yang Dimodifikasi & Dibuat

| Status Berkas | Path Berkas Kode | Peran & Rationale |
| :--- | :--- | :--- |
| **[MODIFIED]** | [`composer.json`](file:///f:/sismon-tte-testing-V4/composer.json) | Upgrade framework ke `"laravel/framework": "^13.0"` dan `"php": "^8.2\|^8.3"` |
| **[NEW]** | [`config/vpn.php`](file:///f:/sismon-tte-testing-V4/config/vpn.php) | Konfigurasi terpusat subnet CIDR IP VPN, header gateway, dan status respon |
| **[NEW]** | [`app/Http/Middleware/EnsureVpnAccess.php`](file:///f:/sismon-tte-testing-V4/app/Http/Middleware/EnsureVpnAccess.php) | Middleware evaluasi bitwise IP CIDR matching dan respon Stealth 404 |
| **[MODIFIED]** | [`bootstrap/app.php`](file:///f:/sismon-tte-testing-V4/bootstrap/app.php) | Pendaftaran alias middleware `'vpn.gateway'` |
| **[MODIFIED]** | [`routes/web.php`](file:///f:/sismon-tte-testing-V4/routes/web.php) | Pemisahan rute auth admin ke `/gateway/login` dan proteksi `/admin/*` via VPN |
| **[MODIFIED]** | [`.env`](file:///f:/sismon-tte-testing-V4/.env) & [`.env.example`](file:///f:/sismon-tte-testing-V4/.env.example) | Penambahan variabel `VPN_GATEWAY_ENABLED`, `VPN_ALLOWED_SUBNETS`, dll. |
| **[MODIFIED]** | [`resources/views/auth/login.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/auth/login.blade.php) | Penambahan badge visual `[● JARINGAN VPN GATEWAY TERVERIFIKASI]` |
| **[MODIFIED]** | [`resources/views/layouts/admin.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/layouts/admin.blade.php) | Penambahan badge visual `[● VPN DISKOMINFO TERVERIFIKASI]` pada topbar |
| **[MODIFIED]** | [`docker/nginx/nginx.conf`](file:///f:/sismon-tte-testing-V4/docker/nginx/nginx.conf) | Hardening Nginx Edge Proxy untuk lokasi `/gateway/` dan `/admin/` |
| **[MODIFIED]** | [`phpunit.xml`](file:///f:/sismon-tte-testing-V4/phpunit.xml) | Penambahan environment variable testing VPN Gateway |
| **[NEW]** | [`tests/Feature/VpnGatewaySecurityTest.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/VpnGatewaySecurityTest.php) | Test suite verifikasi pemblokiran non-VPN (404) dan akses resmi VPN (200) |
| **[MODIFIED]** | [`tests/Feature/AuthenticationTest.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/AuthenticationTest.php) | Penyesuaian endpoint testing dari `/login` ke `/gateway/login` |
| **[MODIFIED]** | [`tests/Feature/FrontendViewRenderTest.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/FrontendViewRenderTest.php) | Penambahan konfigurasi subnet VPN pada test runner |

---

## 5. Komparasi Detail Kode Program: Sistem Sebelumnya vs Sistem Baru

Berikut adalah komparasi mendalam *Before* vs *After* beserta penjelasan teknisnya:

---

### 5.1 [`composer.json`](file:///f:/sismon-tte-testing-V4/composer.json) — Upgrade Framework Core

#### 🔴 Kode Sebelumnya (Laravel 12):
```json
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10.1"
    },
```

#### 🟢 Kode Baru (Laravel 13):
```json
    "require": {
        "php": "^8.2|^8.3",
        "laravel/framework": "^13.0",
        "laravel/tinker": "^2.10.1"
    },
```

> **Penjelasan Perubahan:**
> - Require framework dinaikkan dari `^12.0` menjadi `^13.0` untuk memenuhi standar versi framework enterprise rilis terbaru.
> - Batasan PHP diselaraskan ke `"php": "^8.2|^8.3"` agar kompatibel dengan runtime PHP 8.3-FPM pada kontainer produksi maupun environment host.

---

### 5.2 [`config/vpn.php`](file:///f:/sismon-tte-testing-V4/config/vpn.php) — Konfigurasi Terpusat VPN Gateway (BERKAS BARU)

#### 🔴 Kode Sebelumnya:
*(Berkas belum ada pada sistem sebelumnya)*

#### 🟢 Kode Baru:
```php
<?php

return [
    /* Status Pemeriksaan Perimeter VPN */
    'enabled' => env('VPN_GATEWAY_ENABLED', true),

    /* Daftar Subnet CIDR yang Diizinkan */
    'allowed_subnets' => array_filter(array_map('trim', explode(',', env(
        'VPN_ALLOWED_SUBNETS',
        '127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'
    )))),

    /* Header & Secret Gateway Opsional */
    'gateway_header' => env('VPN_GATEWAY_HEADER', null),
    'gateway_secret' => env('VPN_GATEWAY_SECRET', null),

    /* Respon Penolakan: 404 (Stealth Mode) atau 403 (Forbidden) */
    'block_response' => (int) env('VPN_BLOCK_RESPONSE', 404),

    /* Logging Otomatis Upaya Akses Luar Perimeter */
    'log_violations' => env('VPN_LOG_VIOLATIONS', true),
];
```

> **Penjelasan Perubahan:**
> - Menyediakan konfigurasi terstruktur untuk mengatur whitelist subnet IP (`10.0.0.0/8` untuk VPN Diskominfo, `172.16.0.0/12` untuk Docker internal, `192.168.0.0/16` untuk LAN kantor, `127.0.0.1/32` untuk local dev).
> - Mendukung konfigurasi kode respon `404` (Stealth Mode) agar penyerang dari luar tidak mengetahui keberadaan endpoint admin.

---

### 5.3 [`app/Http/Middleware/EnsureVpnAccess.php`](file:///f:/sismon-tte-testing-V4/app/Http/Middleware/EnsureVpnAccess.php) — Middleware Keamanan Perimeter (BERKAS BARU)

#### 🔴 Kode Sebelumnya:
*(Tidak ada middleware verifikasi VPN — rute login terbuka untuk siapapun dari internet)*

#### 🟢 Kode Baru:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureVpnAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('vpn.enabled', true)) {
            return $next($request);
        }

        $clientIp = $request->ip() ?: '0.0.0.0';
        $allowedSubnets = config('vpn.allowed_subnets', [
            '127.0.0.1/32',
            '::1/128',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        // 1. Bitwise IP Matching terhadap CIDR Subnet
        $isIpAllowed = IpUtils::checkIp($clientIp, $allowedSubnets);

        // 2. Evaluasi Gateway Token jika diaktifkan
        $gatewayHeader = config('vpn.gateway_header');
        $gatewaySecret = config('vpn.gateway_secret');
        $isHeaderValid = true;

        if (!empty($gatewayHeader) && !empty($gatewaySecret)) {
            $incomingHeader = (string) $request->header($gatewayHeader);
            $isHeaderValid = hash_equals((string) $gatewaySecret, $incomingHeader);
        }

        if ($isIpAllowed && $isHeaderValid) {
            return $next($request);
        }

        // 3. Catat Log Peringatan Pelanggaran Perimeter
        if (config('vpn.log_violations', true)) {
            Log::warning('Percobaan akses rute administratif di luar perimeter VPN Gateway', [
                'ip'         => $clientIp,
                'url'        => $request->fullUrl(),
                'method'     => $request->method(),
                'user_agent' => $request->userAgent(),
                'allowed'    => false,
            ]);
        }

        // 4. Respon Stealth 404 (Not Found)
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
```

> **Penjelasan Perubahan:**
> - Menggunakan utilitas native `IpUtils::checkIp()` dari komponen Symfony HTTP Foundation untuk pencocokan subnet IPv4 & IPv6 secara presisi dan performan.
> - Jika IP tidak terdaftar dalam subnet VPN: mencatat detail percobaan ke log sistem dan langsung mematikan request dengan respon **HTTP 404 Not Found (Stealth Mode)**.

---

### 5.4 [`bootstrap/app.php`](file:///f:/sismon-tte-testing-V4/bootstrap/app.php) — Pendaftaran Alias Middleware

#### 🔴 Kode Sebelumnya:
```php
        // Daftarkan alias middleware
        $middleware->alias([
            'throttle.custom'  => \App\Http\Middleware\ThrottlingMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'sanitize.input'   => \App\Http\Middleware\SanitizeInput::class,
        ]);
```

#### 🟢 Kode Baru:
```php
        // Daftarkan alias middleware
        $middleware->alias([
            'vpn.gateway'      => \App\Http\Middleware\EnsureVpnAccess::class,
            'throttle.custom'  => \App\Http\Middleware\ThrottlingMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'sanitize.input'   => \App\Http\Middleware\SanitizeInput::class,
        ]);
```

> **Penjelasan Perubahan:**
> - Mendaftarkan alias `vpn.gateway` agar dapat dipasang pada route group di file `routes/web.php` dengan sintaks bersih dan modular.

---

### 5.5 [`routes/web.php`](file:///f:/sismon-tte-testing-V4/routes/web.php) — Pemisahan & Isolasi Rute Admin

#### 🔴 Kode Sebelumnya (Jalur Bersama Terbuka):
```php
// ── 2. AUTENTIKASI & IAM SUPERADMIN ──────────────────────────────
Route::middleware(['web'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle.custom:login_limit,5,60');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken'])->name('auth.refresh');
});

// ── 3. PANEL KONTROL SUPERADMIN TERPROTEKSI ──────────────────────
Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    // ... rute admin lainnya
});
```

#### 🟢 Kode Baru (Jalur Terisolasi VPN Gateway):
```php
// ── 2. AUTENTIKASI & IAM SUPERADMIN (ISOLASI VPN GATEWAY) ────────
Route::middleware(['web', 'vpn.gateway'])->prefix('gateway')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle.custom:login_limit,5,60');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken'])->name('auth.refresh');
});

// ── 3. PANEL KONTROL SUPERADMIN TERPROTEKSI (VPN GATEWAY + AUTH) ──
Route::middleware(['web', 'vpn.gateway', 'auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    // ... rute admin lainnya
});
```

> **Penjelasan Perubahan:**
> - Rute publik mandiri (`/`, `/pendaftaran`, `/pendaftaran/captcha/refresh`) tetap bersih dan hanya berfokus pada ASN pemohon.
> - Rute `/login` publik dihapus sepenuhnya. Rute login dipindahkan ke `/gateway/login` dan dibungkus middleware `vpn.gateway`.
> - Seluruh panel kendali admin (`/admin/*`) kini memiliki perlindungan ganda: wajib melalui **`vpn.gateway`** dan terautentikasi **`auth`**.

---

### 5.6 [`.env`](file:///f:/sismon-tte-testing-V4/.env) & [`.env.example`](file:///f:/sismon-tte-testing-V4/.env.example) — Variabel Lingkungan VPN

#### 🔴 Kode Sebelumnya:
```env
VITE_APP_NAME="${APP_NAME}"
```

#### 🟢 Kode Baru:
```env
VITE_APP_NAME="${APP_NAME}"

VPN_GATEWAY_ENABLED=true
VPN_ALLOWED_SUBNETS="127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"
VPN_BLOCK_RESPONSE=404
VPN_LOG_VIOLATIONS=true
```

> **Penjelasan Perubahan:**
> - Menyediakan parameter runtime untuk mengontrol IP subnet VPN dan kode status blokir (`404` stealth mode).

---

### 5.7 [`resources/views/auth/login.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/auth/login.blade.php) — Indikator Visual Form Login

#### 🔴 Kode Sebelumnya:
```html
        <div class="app-badge">Pemerintah Kota Cirebon</div>
        <h1 class="login-title">Masuk Superadmin</h1>
        <p class="login-subtitle">Sistem Monitoring TTE & Pelayanan Email Dinas</p>

        @if ($errors->any())
```

#### 🟢 Kode Baru:
```html
        <div class="app-badge">Pemerintah Kota Cirebon</div>
        <h1 class="login-title">Masuk Superadmin</h1>
        <p class="login-subtitle">Sistem Monitoring TTE & Pelayanan Email Dinas</p>
        
        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #e8f5e9; border: 1px solid #24a148; color: #0e6027; font-size: 0.75rem; font-weight: 600; margin-bottom: 1.5rem; letter-spacing: 0.02em;">
            <span style="display: inline-block; width: 8px; height: 8px; background: #24a148;"></span>
            JARINGAN VPN GATEWAY TERVERIFIKASI
        </div>

        @if ($errors->any())
```

> **Penjelasan Perubahan:**
> - Menyematkan badge hijau tegas yang mengonfirmasi bahwa administrator sedang mengakses form melalui jalur VPN yang terenkripsi dan terverifikasi.

---

### 5.8 [`resources/views/layouts/admin.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/layouts/admin.blade.php) — Indikator Visual Header Dashboard

#### 🔴 Kode Sebelumnya:
```html
                <div class="topbar-actions">
                    <div style="font-size: 0.75rem; color: var(--ink-secondary); font-family: 'IBM Plex Mono', monospace;">
                        {{ date('d M Y') }} · <span id="clock-wib">--:--:--</span> WIB
                    </div>

                    <!-- Tombol Logout -->
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
```

#### 🟢 Kode Baru:
```html
                <div class="topbar-actions">
                    <!-- Status VPN Gateway -->
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 8px; background: #e8f5e9; border: 1px solid #24a148; color: #0e6027; font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.02em;">
                        <span style="display: inline-block; width: 6px; height: 6px; background: #24a148;"></span>
                        VPN DISKOMINFO TERVERIFIKASI
                    </div>

                    <div style="font-size: 0.75rem; color: var(--ink-secondary); font-family: 'IBM Plex Mono', monospace;">
                        {{ date('d M Y') }} · <span id="clock-wib">--:--:--</span> WIB
                    </div>

                    <!-- Tombol Logout -->
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
```

> **Penjelasan Perubahan:**
> - Memberikan transparansi status keamanan jaringan real-time kepada Superadmin saat mengelola data verifikasi dan penerbitan sertifikat.

---

### 5.9 [`docker/nginx/nginx.conf`](file:///f:/sismon-tte-testing-V4/docker/nginx/nginx.conf) — Hardening Layer 1 Edge Proxy

#### 🔴 Kode Sebelumnya:
```nginx
        # Rate Limiting Khusus Pendaftaran Publik (5 request per menit)
        location = /pendaftaran {
            limit_req zone=pendaftaran_limit burst=2 nodelay;
            try_files $uri /index.php?$query_string;
        }

        # Rate Limiting Khusus Login Superadmin
        location = /login {
            limit_req zone=pendaftaran_limit burst=3 nodelay;
            try_files $uri /index.php?$query_string;
        }
```

#### 🟢 Kode Baru:
```nginx
        # Rate Limiting Khusus Pendaftaran Publik (5 request per menit)
        location = /pendaftaran {
            limit_req zone=pendaftaran_limit burst=2 nodelay;
            try_files $uri /index.php?$query_string;
        }

        # Rate Limiting & Handling Rute Autentikasi Admin (VPN Gateway)
        location ^~ /gateway/ {
            limit_req zone=pendaftaran_limit burst=5 nodelay;
            try_files $uri /index.php?$query_string;
        }

        # Handling Rute Panel Admin (VPN Gateway)
        location ^~ /admin/ {
            try_files $uri /index.php?$query_string;
        }
```

> **Penjelasan Perubahan:**
> - Memperbarui blok rate limiting dan routing Nginx Edge untuk mencocokkan rute `/gateway/` dan `/admin/` alih-alih rute publik `/login` lama.
> - **Perbaikan Encoding & Entrypoint Container (`docker/nginx/Dockerfile` & `nginx.conf`):** Memastikan berkas disimpan dengan encoding murni **UTF-8 No BOM** untuk mencegah Nginx parser crash (`unknown directive "user"` akibat 3 byte BOM `\xEF\xBB\xBF`) dan membersihkan `/docker-entrypoint.d/*` untuk mengeliminasi konflik `read-only file system` saat inisialisasi container.

---

### 5.10 [`phpunit.xml`](file:///f:/sismon-tte-testing-V4/phpunit.xml) — Konfigurasi Environment Pengujian

#### 🔴 Kode Sebelumnya:
```xml
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
    </php>
```

#### 🟢 Kode Baru:
```xml
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
        <env name="VPN_GATEWAY_ENABLED" value="true"/>
        <env name="VPN_ALLOWED_SUBNETS" value="127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"/>
        <env name="VPN_BLOCK_RESPONSE" value="404"/>
    </php>
```

> **Penjelasan Perubahan:**
> - Menyediakan variabel environment testing agar seluruh test runner mengeksekusi skenario dengan proteksi VPN aktif secara otomatis.

---

### 5.11 [`tests/Feature/VpnGatewaySecurityTest.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/VpnGatewaySecurityTest.php) — Test Suite Baru

#### 🔴 Kode Sebelumnya:
*(Belum ada pengujian otomatis untuk VPN Gateway)*

#### 🟢 Kode Baru (7 Skenario Pengujian):
```php
class VpnGatewaySecurityTest extends TestCase
{
    // 1. IP Publik Asing DITOLAK saat akses /gateway/login (404 Stealth)
    public function test_public_user_cannot_access_gateway_login_from_unauthorized_public_ip();

    // 2. IP Publik Asing DITOLAK saat akses /admin/dashboard (404 Stealth)
    public function test_public_user_cannot_access_admin_dashboard_from_unauthorized_public_ip();

    // 3. POST login dari IP Publik Asing DITOLAK (404 Stealth)
    public function test_public_user_cannot_post_login_from_unauthorized_public_ip();

    // 4. Subnet IP VPN (10.10.1.50) DIIZINKAN mengakses form login (200 OK)
    public function test_authorized_vpn_subnet_can_access_gateway_login();

    // 5. Subnet IP VPN (10.10.1.50) DIIZINKAN melakukan autentikasi login (200 OK)
    public function test_authorized_vpn_subnet_can_authenticate_successfully();

    // 6. Superadmin via VPN DIIZINKAN mengakses panel dashboard (200 OK)
    public function test_authorized_vpn_user_can_access_admin_dashboard_after_login();

    // 7. Toggle non-aktif VPN dapat digunakan saat bypass lokal
    public function test_vpn_protection_can_be_disabled_via_configuration();
}
```

> **Penjelasan Perubahan:**
> - Memberikan jaminan otomatis (*automated quality regression*) bahwa rute administratif terproteksi 100% dari akses tidak sah luar jaringan.

---

### 5.12 [`tests/Feature/AuthenticationTest.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/AuthenticationTest.php) — Penyelarasan Rute Testing

#### 🔴 Kode Sebelumnya:
```php
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk Superadmin');
    }

    public function test_superadmin_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->postJson('/login', [ ... ]);
        // ...
    }
```

#### 🟢 Kode Baru:
```php
    public function test_login_page_can_be_rendered_via_vpn(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/gateway/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk Superadmin');
        $response->assertSee('JARINGAN VPN GATEWAY TERVERIFIKASI');
    }

    public function test_superadmin_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/gateway/login', [ ... ]);
        // ...
    }
```

> **Penjelasan Perubahan:**
> - Seluruh endpoint testing diarahkan ke `/gateway/login`, `/gateway/logout`, dan `/gateway/auth/*` dengan simulasi IP subnet yang sah.

---

## 6. Ringkasan Evaluasi Arsitektur

```
+----------------------------------------------------------------------------------------------------+
|                                 KOMPARASI FITUR & KEAMANAN SISTEM                                  |
+--------------------------+-------------------------------------+-----------------------------------+
| Parameter Evaluasi       | Sistem Sebelumnya (V3.0)            | Sistem Baru (V3.1 - Sekarang)     |
+--------------------------+-------------------------------------+-----------------------------------+
| Framework Core           | Laravel 12                          | Laravel 13                        |
| Rute Form Publik         | GET /                               | GET /                             |
| Rute Submit Pendaftaran  | POST /pendaftaran                   | POST /pendaftaran                 |
| Rute Autentikasi Admin   | GET/POST /login (Terbuka Publik) ⚠️ | GET/POST /gateway/login (VPN) 🛡️  |
| Respon Akses Admin Luar  | Form Login Terbuka / 401 Unauth ⚠️  | HTTP 404 Not Found (Stealth) 🛡️  |
| Lapisan Inspeksi IP      | Tidak Ada (Siapapun bisa buka login)| Subnet CIDR Bitwise Matcher       |
| Indikator Visual Admin   | Standar Topbar                      | Badge [● VPN DISKOMINFO]          |
| Perlindungan Edge Nginx  | Rate limit umum pada /login         | Rate limit + isolasi /gateway/    |
| Automated Test Suite     | 38 Tests                            | 45 Tests (+7 VPN Security Tests)  |
+--------------------------+-------------------------------------+-----------------------------------+
```

---

## 7. Rincian Revisi V3.1.1 (Penghapusan Berkas, Masking URL, Pagination Proporsional, dan reCAPTCHA v2)

### 7.1 Penghapusan Fitur Unggah Berkas Persyaratan & Pratinjau Admin
- **Latar Belakang:** Alur permohonan TTE difokuskan pada pengumpulan data identitas ASN (Nama, NIK, NIP, Nomor Kontak WhatsApp, SKPD, Jabatan, Golongan) dan status usulan akun email dinas resmi (`@cirebonkota.go.id`) tanpa mewajibkan unggah berkas fisik lampiran.
- **Perubahan Kode Program:**
  1. [`app/Http/Requests/StorePendaftaranRequest.php`](file:///f:/sismon-tte-testing-V4/app/Http/Requests/StorePendaftaranRequest.php): Menghapus aturan validasi `'lampiran' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']` dan pesan error terkait.
  2. [`app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php): Menghapus penyimpanan berkas fisik `storeLampiran()` dan relasi lampiran pada transaksi pendaftaran.
  3. [`resources/views/public/pendaftaran.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/public/pendaftaran.blade.php): Menghapus dropzone input berkas lampiran beserta event listener JavaScript.
  4. [`resources/views/admin/verifikasi/show.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/admin/verifikasi/show.blade.php): Menghapus kolom pratinjau dokumen (iframe PDF / preview gambar) sehingga tampilan verifikasi menjadi lebih bersih, fokus, dan terpusat pada validasi data kepegawaian ASN serta penetapan email dinas definitif.

### 7.2 Penggantian Alamat Web ID Angka (`/admin/verifikasi/2`) Menjadi Nomor Referensi Resmi
- **Alasan Penggunaan Angka Sebelumnya:** Angka `2` berasal dari primary key auto-increment database (`id`), yang secara standar digunakan pada RESTful routing `GET /admin/verifikasi/{id}`.
- **Risiko Angka Database (IDOR / Integer Enumeration):** URL dengan ID integer berurutan (`1`, `2`, `3`) mempermudah penyerang menebak atau mengeksploitasi data permohonan lain secara berurutan (*sequential guessing*).
- **Solusi yang Diterapkan:**
  - Seluruh tautan navigasi dan endpoint verifikasi diubah dari ID integer menjadi **Nomor Referensi Resmi Pendaftaran** (contoh: `https://localhost/admin/verifikasi/TTE-20260819-TS0002`).
  - Controller [`VerifikasiController.php`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/VerifikasiController.php) dan [`PenerbitanController.php`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/PenerbitanController.php) diperbarui dengan method pencarian `findPendaftaran()` yang fleksibel mencari berdasarkan `nomor_referensi` unik sehingga tidak ada angka ID database yang terekspos ke bilah alamat browser.

### 7.3 Perbaikan Tombol Previous & Next pada Menu Penerbitan (IBM Carbon Pagination)
- **Akar Masalah Tombol Tidak Proporsional:** Tanpa styling khusus, pemanggilan `$sertifikatList->links()` menghasilkan tombol dengan ukuran tidak seimbang dan ikon panah SVG default yang meregang secara tidak proporsional.
- **Solusi yang Diterapkan:**
  1. Membuat template pagination resmi [`resources/views/vendor/pagination/carbon.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/vendor/pagination/carbon.blade.php) berbasis IBM Carbon Design System.
  2. Menambahkan styling CSS presisi di [`public/css/app.css`](file:///f:/sismon-tte-testing-V4/public/css/app.css) dengan tinggi tombol seragam (34px), padding proporsional (`0.4rem 0.8rem`), kontras tajam, indikator halaman aktif, dan pembatasan ukuran ikon SVG maksimal 14px.
  3. Memperbarui view [`resources/views/admin/penerbitan/index.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/admin/penerbitan/index.blade.php) dan [`resources/views/admin/verifikasi/index.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/admin/verifikasi/index.blade.php) agar memanggil `$list->links('vendor.pagination.carbon')`.

### 7.4 Penambahan Checkbox Widget Google reCAPTCHA v2
- **Implementasi:**
  1. Memasang script resmi Google reCAPTCHA API (`https://www.google.com/recaptcha/api.js`) pada formulir publik ASN.
  2. Menempatkan widget `<div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>` tepat di atas tantangan keamanan matematika lokal.
  3. Menjaga mekanisme *graceful fallback* di mana jika kunci reCAPTCHA tidak aktif/offline, validasi Local Math CAPTCHA tetap menjaga sistem dari serangan automated bot.

### 7.5 Penguatan Fleksibilitas Verifikasi & Pengeditan Email Dinas (Tahap 1)
- **Latar Belakang & Kebutuhan:**
  Admin Superadmin memerlukan fleksibilitas penuh untuk memvalidasi, mengoreksi, atau mengubah alamat email definitif yang akan diterbitkan sertifikat TTE-nya, baik untuk pemohon ASN yang menyatakan **sudah memiliki email** (`email_existing`) maupun yang **belum memiliki email** (`email_usulan`).
- **Perubahan yang Diterapkan:**
  1. [`resources/views/admin/verifikasi/show.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/admin/verifikasi/show.blade.php):
     - Menampilkan info banner pemohon ASN (`SUDAH MEMILIKI EMAIL` atau `BELUM MEMILIKI EMAIL`) disertai tombol cepat `[↙ Salin ke Input Definitif]`.
     - Form input penetapan email definitif (`#email_final_input`) beserta tombol aksi `[Kunci & Rekap]` kini **selalu tersedia dan aktif** bagi Superadmin pada seluruh data antrian verifikasi.
     - Ditambahkan validasi domain client-side dan server-side (`@cirebonkota.go.id`) serta indikator badge konfirmasi rekapitulasi real-time via AJAX.
  2. [`app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php):
     - Memperbarui mekanisme fallback otomatis saat persetujuan permohonan dieksekusi, sehingga jika admin belum menekan tombol rekap manual, sistem otomatis mengunci nilai `email_final_terverifikasi` sesuai email existing (jika sudah ada) atau email usulan (jika belum ada).
  3. [`tests/Feature/VerifikasiTahap1Test.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/VerifikasiTahap1Test.php):
     - Menambahkan kasus uji otomatis `test_superadmin_can_edit_and_recap_email_even_if_applicant_has_existing_email()` untuk memvalidasi alur pengeditan email existing oleh Superadmin.

---

> **Catatan Sebelumnya:**
> Seksi 1 s/d 7 di atas mendokumentasikan evolusi sistem hingga rilis V3.1.1 (19-23 Agustus 2026) yang meliputi migrasi ke Laravel 13, segmentasi Zero-Trust VPN Gateway, penghapusan lampiran berkas fisik, nomor referensi URL resmi, styling pagination IBM Carbon, dan reCAPTCHA v2.
> 
> Seksi 8 di bawah ini merupakan kelanjutan resmi yang mencatat **Perubahan & Remediasi Menyeluruh Hasil Audit Kode Program V3.2.0-PROD (24 Agustus 2026)** sesuai panduan [AUDIT.md](file:///f:/sismon-tte-testing-V4/AUDIT.md).

---

## 8. Rincian Revisi V3.2.0 (Remediasi Menyeluruh Hasil Audit Kode Program 4 Pilar)

### 8.1 Pilar Keamanan: Perlindungan PII di Browser & Step-Up Auth pada MFA
1. **Eliminasi PII NIK & NIP dari Storage Browser ([`resources/views/public/pendaftaran.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/public/pendaftaran.blade.php)):**
   - Menghapus field sensitif `nik` dan `nip` dari fungsi auto-save JavaScript `saveDraft()`.
   - Mengalihkan penyimpanan draft lokal dari `localStorage` permanen ke `sessionStorage` temporer (`tte_registration_draft_temp`).
   - Menambahkan event listener submit untuk membersihkan draft formulir secara otomatis saat dikirim.
2. **Step-Up Authentication & RBAC pada `disableMfa` ([`app/Http/Controllers/UserManagementController.php`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/UserManagementController.php)):**
   - Menambahkan validasi `current_password` sebelum MFA dapat dimatikan.
   - Memverifikasi wewenang eksekusi: hanya user pemilik akun atau Superadmin utama yang diizinkan.
   - Menerbitkan event audit log `ProcessAuditLogJob` dengan action `DISABLE_MFA_USER`.
3. **Persistensi Recovery Codes Terenkripsi ([`app/Models/User.php`](file:///f:/sismon-tte-testing-V4/app/Models/User.php) & Migrasi `000010`):**
   - Menambahkan kolom `mfa_recovery_codes` dengan enkripsi otomatis (`cast 'encrypted'`) pada tabel `users`.
   - Menyimpan 8 kode cadangan ke database saat `confirmMfa()`.
4. **Penerapan Eksplisit Policy Enforcement ([`app/Http/Controllers/Controller.php`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/Controller.php) & [`app/Providers/AppServiceProvider.php`](file:///f:/sismon-tte-testing-V4/app/Providers/AppServiceProvider.php)):**
   - Menambahkan trait `AuthorizesRequests` dan `ValidatesRequests` pada base class `Controller.php`.
   - Mendaftarkan binding `Gate::policy()` untuk `PendaftaranTtePolicy` dan `SertifikatTtePolicy`.
   - Memanggil `$this->authorize()` pada seluruh method aksi di `VerifikasiController`, `PenerbitanController`, dan `ExportLaporanController`.

### 8.2 Pilar Basis Data: Eliminasi Badai Kueri N+1 & Default Partition Resilience
1. **Optimasi Mass Cursor Streaming Export ([`app/Http/Controllers/ExportLaporanController.php`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/ExportLaporanController.php)):**
   - Mengubah kueri Eloquent `with()` di dalam `cursor()` menjadi query builder `DB::table('pendaftaran_tte')->leftJoin(...)` terpadu.
   - Menjamin ekspor ratusan ribu baris data CSV berjalan dalam 1 kueri tunggal bebas N+1 query storm dengan konsumsi RAM < 20MB.
2. **Resiliensi Partisi Log Audit ([`database/migrations/2026_08_01_000011_add_default_partition_to_security_audit_logs.php`](file:///f:/sismon-tte-testing-V4/database/migrations/2026_08_01_000011_add_default_partition_to_security_audit_logs.php)):**
   - Menambahkan DDL PostgreSQL `CREATE TABLE IF NOT EXISTS security_audit_logs_default PARTITION OF security_audit_logs DEFAULT;` sebagai partisi fallback.

### 8.3 Pilar Kualitas Kode: Sanitasi, Validasi & Exception Handler DRY
1. **Pembersihan Duplikasi Validasi ([`app/Http/Requests/StorePendaftaranRequest.php`](file:///f:/sismon-tte-testing-V4/app/Http/Requests/StorePendaftaranRequest.php)):**
   - Menghapus deklarasi ganda pada kunci array validasi `status_memiliki_email` dan `email_existing`.
2. **Penyelarasan Regex Usulan Email Dinas ([`resources/views/public/pendaftaran.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/public/pendaftaran.blade.php)):**
   - Menyelaraskan regex JavaScript `updateEmailPreview()` dengan pola komprehensif `EmailGeneratorService` backend.
3. **Refaktor Exception Handler DRY ([`bootstrap/app.php`](file:///f:/sismon-tte-testing-V4/bootstrap/app.php)):**
   - Menyederhanakan penanganan error terpusat API/AJAX menggunakan closure tunggal `$jsonResponder`.
4. **Optimasi Entropi Nomor Referensi ([`app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php)):**
   - Mengoptimalkan pembangkitan kode acak 6 digit berbasis binary entropy dengan pembatas loop maksimal.

### 8.4 Pilar Infrastruktur: Strict Secret Enforcement & Unbuffered Streaming
1. **Penegakan Mandatory Secrets ([`docker-compose.yml`](file:///f:/sismon-tte-testing-V4/docker-compose.yml)):**
   - Menghilangkan default password `secret` dan menerapkan `${DB_PASSWORD:?...}` serta `${REDIS_PASSWORD:?...}`.
2. **FastCGI Unbuffered Export Nginx ([`docker/nginx/nginx.conf`](file:///f:/sismon-tte-testing-V4/docker/nginx/nginx.conf)):**
   - Menambahkan blok location dedicated `/admin/export/` dengan `fastcgi_buffering off;` dan `fastcgi_read_timeout 300;` pada HTTP (Port 80) dan HTTPS (Port 443).

---

## 9. Rincian Revisi V3.2.1 (Hardening Reverse Proxy, Dynamic Env Sync & Strict True-Origin VPN Gateway)
**Tanggal Rilis:** 28 Agustus 2026

### 9.1 Trust Proxy & Enforced HTTPS pada Reverse Proxy / Cloudflare Tunnel
1. **Pendaftaran Trust Proxies Global ([`bootstrap/app.php`](file:///f:/sismon-tte-testing-V4/bootstrap/app.php)):**
   - Menambahkan `$middleware->trustProxies(at: '*');` agar framework Laravel memvalidasi dan mempercayai header upstream proxy (`X-Forwarded-Proto`, `X-Forwarded-For`, `CF-Connecting-IP`).
2. **Penegakan Skema HTTPS pada Asset URL ([`app/Providers/AppServiceProvider.php`](file:///f:/sismon-tte-testing-V4/app/Providers/AppServiceProvider.php)):**
   - Menambahkan `URL::forceScheme('https')` saat aplikasi berjalan di lingkungan production, domain HTTPS, atau saat menerima header upstream `HTTP_X_FORWARDED_PROTO=https`.
   - Mengeliminasi error *Mixed Content* dan pemblokiran stylesheet `app.css` oleh *Content Security Policy (CSP)* pada browser klien.

### 9.2 Real-Time Dynamic Env Sync pada Konfigurasi VPN Subnet
1. **Dynamic File Parser ([`config/vpn.php`](file:///f:/sismon-tte-testing-V4/config/vpn.php)):**
   - Memperbarui loader `allowed_subnets` dengan closure dynamic regex parser yang membaca langsung nilai `VPN_ALLOWED_SUBNETS` dari file fisik `.env`.
   - Menghilangkan kendala *Dotenv OS Level Freeze* pada Docker Compose, sehingga pembaruan whitelist IP di `.env` langsung tersinkronisasi instan setelah `php artisan config:clear` tanpa perlu me-recreate kontainer Docker.

### 9.3 Penegakan Strict True-Origin IP pada Perimeter VPN Gateway
1. **Evaluasi Tunggal IP Asal Pengguna ([`app/Http/Middleware/EnsureVpnAccess.php`](file:///f:/sismon-tte-testing-V4/app/Http/Middleware/EnsureVpnAccess.php)):**
   - Mengubah mekanisme ekstraksi IP agar HANYA mengambil satu IP Asal Pengguna (*True Origin IP*) dengan urutan prioritas:
     1. `CF-Connecting-IP` (Header resmi Cloudflare)
     2. IP pertama pada `X-Forwarded-For`
     3. `X-Real-IP`
     4. `$request->ip()`
   - Mengeliminasi total seluruh evaluasi terhadap hop perantara internal proxy dan Docker bridge (`REMOTE_ADDR`), mencegah potensi kebocoran akses (*false positive whitelist*).
   - Menjamin bahwa hanya IP perangkat asli yang terdaftar di `VPN_ALLOWED_SUBNETS` yang dapat mengakses `/gateway/login` dan `/admin/*`, sementara seluruh trafik luar tetap ditolak dengan respon **HTTP 404 Not Found (Stealth Mode)**.

---

## 10. Rincian Revisi V3.3.0 (Sanitasi Nama Lengkap KTP Tanpa Gelar/Tanda Baca, Usulan Email Dinas Terstandarisasi, & Superadmin Quick Collision Check)
**Tanggal Rilis:** 28 Agustus 2026

### 10.1 Sanitasi dan Format Field Nama Lengkap Sesuai KTP
1. **Eliminasi Gelar Otomatis (Akademik & Keagamaan) ([`app/Services/EmailGeneratorService.php`](file:///f:/sismon-tte-testing-V4/app/Services/EmailGeneratorService.php)):**
   - Mengembangkan metode `cleanFullName(string $nama): string` yang secara otomatis memindai dan menghapus:
     - **Gelar Akademik & Profesi/Vokasi:** `Prof.`, `Profesor`, `Dr.`, `Drg.`, `Drs.`, `Dra.`, `Ir.`, `S.Kom`, `S.Pd`, `S.ST`, `S.Tr`, `S.Sos`, `S.E`, `S.H`, `S.Ked`, `S.Farm`, `S.Si`, `S.T`, `S.IP`, `S.AP`, `S.AB`, `S.Kep`, `S.Gz`, `S.Psi`, `S.Stat`, `S.Ak`, `SKM`, `M.Kom`, `M.Pd`, `M.Si`, `M.M`, `M.E`, `M.H`, `M.TI`, `M.T`, `M.AP`, `M.AB`, `M.Kn`, `M.Kes`, `M.Farm`, `M.Psi`, `M.Sc`, `M.Eng`, `M.Ed`, `M.Phil`, `M.A`, `M.Hum`, `MBA`, `MKM`, `Ph.D`, `D.Sc`, `DBA`, `Sp.A`, `Sp.B`, `Sp.PD`, `Sp.OG`, `Sp.M`, `Sp.THT`, `Sp.JP`, `Sp.S`, `Sp.An`, `Sp.Rad`, `Sp.PK`, `Sp.PA`, `Sp.KJ`, `Sp.KK`, `Sp.OT`, `Sp.DV`, `Ners`, `Apt`, `Gr`, `Psikolog`, `Adv`, `CPA`, `CA`, `QIA`, `A.Md`, `A.Md.Kom`, `A.Md.Keb`, `A.Md.Kep`, `A.Md.Farm`, `A.Ma`, `D.III`, `D.IV`, dll.
     - **Gelar Keagamaan & Ulama:** `Haji`, `Hajah`, `H.`, `H`, `Hj.`, `Hj`, `Ustadz`, `Ust.`, `Ust`, `Kiai Haji`, `Kiai`, `K.H.`, `KH.`, `KH`, `Habib`, `Pdt.`, `Pastur`, `Romo`, dll.
   - **Retensi Nama Adat KTP:** Unsur nama adat (seperti `Raden`, `Tubagus`, `Cut`, `Tengku`, `Teuku`, `Andi`, `Sutan`, `Gusti`, `Ida Bagus`, `Cokorda`, `Anak Agung`) yang tercantum pada KTP asli tetap dipertahankan sebagai bagian dari nama lengkap ASN.
2. **Pembersihan Mutlak Tanda Baca Titik (`.`) dan Koma (`,`):**
   - Menghapus seluruh karakter titik dan koma dari input nama lengkap sehingga sesuai dengan standar data kependudukan KTP Disdukcapil murni alfabet.
   - Format teks akhir dikonversi ke *Title Case* bersih (contoh: `"Moh. Ma'ruf, S.Ag."` $\rightarrow$ `"Moh Ma'ruf"`).
3. **Penyimpanan Data Murni Tanpa Gelar ([`app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php)):**
   - Mengintegrasikan `cleanFullName` pada proses penyimpanan awal pendaftaran sehingga data yang tersimpan pada basis data `pendaftaran_tte.nama_lengkap` dipastikan murni nama KTP tanpa gelar dan tanpa tanda baca.
4. **Formulir Pendaftaran Publik ([`resources/views/public/pendaftaran.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/public/pendaftaran.blade.php)):**
   - Label diubah menjadi **"Nama Lengkap Sesuai KTP"** dengan petunjuk: *"Isi nama lengkap sesuai KTP tanpa gelar akademik/keagamaan dan tanpa tanda baca titik (.) atau koma (,)."*
   - Placeholder disederhanakan: `"Contoh: Budi Santoso"`.
   - Menghapus sisa atribut `enctype="multipart/form-data"` pada tag form pendaftaran publik.

### 10.2 Logika Pembentukan Usulan Email Dinas Terstandarisasi (`@cirebonkota.go.id`)
1. **Algoritma Usulan Standar ([`app/Services/EmailGeneratorService.php`](file:///f:/sismon-tte-testing-V4/app/Services/EmailGeneratorService.php)):**
   - **Pendaftar dengan Nama > 1 Kata:** Menggunakan kombinasi **2 suku kata nama depan** (huruf kecil) dihubungkan dengan *underscore* (`_`), diikuti oleh **2 digit awal NIP**.
     $$\text{"Ajria Jundan Jahid Idris" (NIP: 20...)} \longrightarrow \mathbf{ajria\_jundan\_20@cirebonkota.go.id}$$
   - **Pendaftar dengan Nama 1 Kata:** Menggunakan **nama pendaftar** (huruf kecil) diikuti oleh **4 digit awal NIP**.
     $$\text{"Budi" (NIP: 1990...)} \longrightarrow \mathbf{budi\_1990@cirebonkota.go.id}$$
2. **Mekanisme Penanganan Duplikasi Bertingkat (*Fallback Mechanism*):**
   - **Tahap 1 (Duplikasi Awal):** Jika email standar telah terdaftar atas nama ASN lain, sistem menambahkan 2 digit NIP berikutnya (sehingga menggunakan **total 4 digit awal NIP**):
     $$\mathbf{ajria\_jundan\_2001@cirebonkota.go.id}$$
   - **Tahap 2 (Duplikasi Lanjutan):** Jika usulan 4 digit NIP masih duplikat, sistem menghasilkan usulan kombinasi acak elemen nama dan angka NIP dengan **batasan maksimal 4 digit angka NIP**.
3. **Sinkronisasi Live Preview JavaScript:**
   - Menyelaraskan fungsi `updateEmailPreview()` pada `pendaftaran.blade.php` agar pratinjau live email pemohon di sisi browser 100% identik dengan hasil generate resmi di sisi backend.

### 10.3 Pengendalian Unisitas & Fitur Quick Collision Check Superadmin
1. **Pemeriksaan Tabrakan Email Lintas Tabel Database:**
   - Metode `isEmailTaken(string $email, ?int $excludeId)` memvalidasi bahwa alamat email belum pernah digunakan di:
     - `pendaftaran_tte` pada kolom `email_usulan`, `email_final_terverifikasi`, maupun `email_existing` (dengan status selain `DITOLAK`).
     - `sertifikat_tte` pada kolom `email_dinas`.
2. **Penegakan Validasi Saat Approval & Rekapitulasi:**
   - [`ApproveTahap1Action.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php) dan [`RekapEmailDinasAction.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/RekapEmailDinasAction.php) melempar `ValidationException` jika Superadmin mencoba menyetujui atau merekap email yang telah terdaftar/bentrok.
3. **Endpoint & Antarmuka Cek Ringkas Duplikasi (Quick Collision Check):**
   - **Endpoint API:** Ditambahkan route [`POST /admin/verifikasi/{id}/check-collision`](file:///f:/sismon-tte-testing-V4/routes/web.php) yang ditangani oleh [`VerifikasiController::checkCollision()`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/VerifikasiController.php).
   - **Panel Superadmin ([`resources/views/admin/verifikasi/show.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/admin/verifikasi/show.blade.php)):**
     - Disediakan tombol **`🔍 Cek Unisitas`** di samping input email definitif.
     - Disediakan container notifikasi visual real-time (`#collision-result-box`) yang menampilkan status ketersediaan email, atau rincian ASN/SKPD pemilik jika email bentrok, sebelum tombol approval ditekan.

---

### 10.4 Matriks Berkas yang Dimodifikasi pada Revisi V3.3.0

| Status Berkas | Path Berkas Kode | Ringkasan Perubahan |
| :--- | :--- | :--- |
| **[MODIFIED]** | [`app/Services/EmailGeneratorService.php`](file:///f:/sismon-tte-testing-V4/app/Services/EmailGeneratorService.php) | Implementasi `cleanFullName()` (eliminasi gelar akademik/keagamaan & titik/koma), `generate()` pola 2 kata + 2/4 digit NIP, `isEmailTaken()`, dan `checkCollisionDetail()`. |
| **[MODIFIED]** | [`app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/StorePendaftaranAction.php) | Integrasi sanitasi nama KTP sebelum create record, pembersihan komentar usang file upload. |
| **[MODIFIED]** | [`app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php) | Validasi unisitas email sebelum persetujuan verifikasi Tahap 1. |
| **[MODIFIED]** | [`app/Domain/Pendaftaran/Actions/RekapEmailDinasAction.php`](file:///f:/sismon-tte-testing-V4/app/Domain/Pendaftaran/Actions/RekapEmailDinasAction.php) | Validasi unisitas email saat penguncian rekapitulasi email definitif. |
| **[MODIFIED]** | [`app/Http/Controllers/VerifikasiController.php`](file:///f:/sismon-tte-testing-V4/app/Http/Controllers/VerifikasiController.php) | Penambahan method `checkCollision()` untuk endpoint AJAX Superadmin. |
| **[MODIFIED]** | [`routes/web.php`](file:///f:/sismon-tte-testing-V4/routes/web.php) | Pendaftaran route `POST /admin/verifikasi/{id}/check-collision` (`admin.verifikasi.check-collision`). |
| **[MODIFIED]** | [`resources/views/admin/verifikasi/show.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/admin/verifikasi/show.blade.php) | Penambahan tombol Cek Unisitas, banner status collision AJAX, dan script `checkCollisionAjax()`. |
| **[MODIFIED]** | [`resources/views/public/pendaftaran.blade.php`](file:///f:/sismon-tte-testing-V4/resources/views/public/pendaftaran.blade.php) | Penyesuaian label nama KTP tanpa titik/koma/gelar, update JS `updateEmailPreview()`, penghapusan `enctype`. |
| **[MODIFIED]** | [`tests/Unit/EmailGeneratorServiceTest.php`](file:///f:/sismon-tte-testing-V4/tests/Unit/EmailGeneratorServiceTest.php) | 8 test cases komprehensif: sanitasi nama KTP, multi-word, single-word, fallback duplikasi tahap 1 & 2, collision check, dan domain validator. |
| **[MODIFIED]** | [`tests/Feature/PendaftaranPublikTest.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/PendaftaranPublikTest.php) | Penyesuaian assertion nama bersih tanpa gelar dan format email usulan baru. |
| **[MODIFIED]** | [`tests/Feature/VerifikasiTahap1Test.php`](file:///f:/sismon-tte-testing-V4/tests/Feature/VerifikasiTahap1Test.php) | Penambahan test case endpoint `check-collision` dan penolakan rekap email duplikat. |

---

### 10.5 Hasil Verifikasi Pengujian (*Automated Testing Verification*)
Semua pengujian otomatis dieksekusi di dalam kontainer produksi dan **lulus 100%**:
- **Unit Tests:** `EmailGeneratorServiceTest` $\rightarrow$ **8/8 PASSED (32 assertions)**
- **Unit Tests Keseluruhan:** `tests/Unit/` $\rightarrow$ **15/15 PASSED (50 assertions)**
- **Feature Tests:** `PendaftaranPublikTest` & `VerifikasiTahap1Test` $\rightarrow$ **10/10 PASSED (51 assertions)**

---


---

## 11. Rincian Revisi V5.1.0 (Dashboard Top SKPD Berbasis Persentase Kepatuhan ASN & Eliminasi Size Bias)

### 11.1 Latar Belakang & Analisis Permasalahan Size Bias
Pada implementasi dashboard sebelumnya, pemeringkatan **Top 10 SKPD** mengurutkan instansi murni berdasarkan volume permohonan pendaftar (`COUNT(*)`). Hal ini memicu permasalahan struktural berupa **Size Bias**:
1. **Bias Instansi Besar:** Unit kerja berpopulasi ratusan pegawai (misal: Dinas Pendidikan dengan 200 ASN, Dinas Kesehatan dengan 120 ASN) selalu menempati peringkat teratas meski tingkat kepatuhan adopsi pegawai rendah (contoh: 50 pendaftar dari 200 ASN = 25%).
2. **Keterasingan Instansi Ramping:** Unit kerja berukuran kecil (seperti Inspektorat dengan 30 ASN, atau Kecamatan Kejaksan dengan 25 ASN) yang telah mendorong 80%–100% pegawainya mendaftar TTE tidak pernah masuk ke dalam daftar Top 10.
3. **Ketidakakuratan KPI Evaluasi Pimpinan:** Pimpinan daerah memerlukan data representatif mengenai instansi mana yang paling taat/patuh menyelesaikan pendaftaran TTE seluruh pegawainya, tanpa menghilangkan visibilitas terhadap instansi mana yang menghasilkan beban volume kerja tertinggi bagi verifikator Diskominfo.

---

### 11.2 Penambahan Atribut `total_asn` & Migrasi Skema Basis Data
1. **Migrasi Basis Data ([`database/migrations/2026_09_09_000001_add_total_asn_to_skpd_table.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/database/migrations/2026_09_09_000001_add_total_asn_to_skpd_table.php)):**
   - Menambahkan kolom `total_asn INTEGER NOT NULL DEFAULT 0` pada tabel `skpd`.
   - Migration diuji dan dieksekusi sukses pada PostgreSQL 16 container `tte_postgres`.
2. **Domain Model (`Skpd.php`):**
   - Mendaftarkan `total_asn` ke dalam array `$fillable`.
   - Menambahkan type casting `'total_asn' => 'integer'` pada method `casts()`.
3. **Penyempurnaan Seeder ([`database/seeders/SkpdSeeder.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/database/seeders/SkpdSeeder.php)):**
   - Mengalihkan metode dari `firstOrCreate` ke `updateOrCreate` agar data populasi 20 SKPD Kota Cirebon langsung diperbarui secara idempotens.
   - Contoh data populasi: Diskominfo (45), Bappeda (55), BPKAD (65), BKPSDM (50), Dinkes (120), Disdik (200), DPUPR (85), Dishub (60), Setda (90), Kecamatan (20-25).
4. **Pembaruan Modul Master SKPD:**
   - **[`app/Http/Controllers/MasterSkpdController.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/app/Http/Controllers/MasterSkpdController.php):** Memvalidasi `'total_asn' => 'required|integer|min:0'` pada method `store()` dan `update()`.
   - **[`resources/views/admin/master/skpd/index.blade.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/resources/views/admin/master/skpd/index.blade.php):** Menambahkan kolom tabel "Total ASN" dan input field pada modal Tambah/Edit SKPD.

---

### 11.3 Logika Query, Formula Capaian Adopsi & Caching Hybrid Redis
1. **Query Sorting Dinamis ([`app/Http/Controllers/DashboardController.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/app/Http/Controllers/DashboardController.php)):**
   - Mendukung parameter query HTTP `?sort=persentase` (default) dan `?sort=volume`.
   - Perhitungan persentase efisien langsung di level database via SQL expression:
     ```sql
     CASE 
         WHEN total_asn > 0 THEN ROUND((COUNT(p.id)::numeric / s.total_asn::numeric) * 100, 1)
         ELSE 0 
     END AS persentase_capaian
     ```
   - Pengurutan data:
     - **Mode Persentase:** `ORDER BY persentase_capaian DESC, pendaftar DESC`
     - **Mode Volume:** `ORDER BY pendaftar DESC, persentase_capaian DESC`
2. **Pemisahan Kunci Cache Redis (Zero Colliding Cache):**
   - Alih-alih menggunakan 1 kunci `dashboard_stats_summary`, sistem membagi cache menjadi:
     - `dashboard_stats_summary_persentase` (TTL 1 jam)
     - `dashboard_stats_summary_volume` (TTL 1 jam)
   - Transisi antar-filter berlangsung instan tanpa proses query berulang ke PostgreSQL.

---

### 11.4 Desain Antarmuka Hybrid Widget Top SKPD
**File:** [`resources/views/admin/dashboard.blade.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/resources/views/admin/dashboard.blade.php)

Pembaruan widget menghadirkan visualisasi intuitif dan informatif:
1. **Tombol Toggle Filter Aktif:**
   - Menyediakan tombol switch berlabel `📊 Kepatuhan (%)` dan `📋 Volume` di pojok kanan header widget dengan styling seleksi aktif.
2. **Judul & Subjudul Adaptif:**
   - Mode Kepatuhan: *"Peringkat Kepatuhan Adopsi TTE per SKPD (Top 10)"* — *"Berdasarkan persentase pendaftar terhadap total populasi ASN..."*
   - Mode Volume: *"Distribusi Volume Permohonan TTE per SKPD (Top 10)"* — *"Rekapitulasi volume transaksi pemohon per instansi..."*
3. **Tabel Data 8 Kolom:**
   - `#` (Nomor Urut Peringkat 1 s/d 10)
   - `Kode`
   - `Nama Unit Kerja (SKPD)`
   - `Total ASN` (Populasi pegawai terdaftar)
   - `Pendaftar` (Jumlah berkas masuk)
   - `Disetujui` (Jumlah lolos verifikasi)
   - `Antrian` (Menunggu verifikasi)
   - `Capaian Adopsi (%)` (Progress bar visual interaktif)
4. **Visual Progress Bar Adaptif 4 Tingkat:**
   - 🟢 **Hijau** (`var(--success)`): Capaian $\ge 80\%$ (Kepatuhan Optimal / Target Tercapai)
   - 🟡 **Kuning** (`var(--warning)`): Capaian $50\% - 79.9\%$ (Dalam Progres / Akselerasi)
   - 🔴 **Merah** (`var(--danger)`): Capaian $< 50\%$ (Perlu Intervensi / Sosialisasi)
   - ⚪ **Abu-abu** (`var(--ink-muted)`): Nilai $0$ atau Total ASN belum diset
5. **Bar Legenda Indikator Capaian:**
   - Ditempatkan di bawah tabel sebagai acuan visual standar bagi pimpinan dan operator.

---

### 11.5 Matriks Berkas yang Dimodifikasi pada Revisi V5.1.0

| Status Berkas | Path Berkas Kode | Ringkasan Perubahan |
| :--- | :--- | :--- |
| **[NEW]** | [`database/migrations/2026_09_09_000001_add_total_asn_to_skpd_table.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/database/migrations/2026_09_09_000001_add_total_asn_to_skpd_table.php) | Penambahan kolom `total_asn INTEGER DEFAULT 0` pada tabel `skpd`. |
| **[MODIFIED]** | [`app/Domain/Master/Models/Skpd.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/app/Domain/Master/Models/Skpd.php) | Penambahan `total_asn` pada `$fillable` dan integer type casting pada `casts()`. |
| **[MODIFIED]** | [`database/seeders/SkpdSeeder.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/database/seeders/SkpdSeeder.php) | Migrasi ke `updateOrCreate` serta data riil populasi ASN pada 20 SKPD Kota Cirebon. |
| **[MODIFIED]** | [`app/Http/Controllers/MasterSkpdController.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/app/Http/Controllers/MasterSkpdController.php) | Penanganan validasi dan penyimpanan `total_asn` pada method `store()` dan `update()`. |
| **[MODIFIED]** | [`resources/views/admin/master/skpd/index.blade.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/resources/views/admin/master/skpd/index.blade.php) | Penambahan kolom tabel "Total ASN" dan input field modal tambah/edit. |
| **[MODIFIED]** | [`app/Http/Controllers/DashboardController.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/app/Http/Controllers/DashboardController.php) | Dukungan query `?sort=persentase|volume`, kalkulasi SQL `persentase_capaian`, dan pemisahan Redis cache key. |
| **[MODIFIED]** | [`resources/views/admin/dashboard.blade.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/resources/views/admin/dashboard.blade.php) | Redesain widget Top 10 SKPD (toggle filter, 8 kolom, progress bar 4 warna, bar legenda, colspan 8). |
| **[MODIFIED]** | [`tests/Feature/FrontendViewRenderTest.php`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/tests/Feature/FrontendViewRenderTest.php) | Penambahan assertion pengujian kedua mode sorting dashboard (14 assertions lulus). |
| **[MODIFIED]** | [`docs/SCHEMA.md`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/docs/SCHEMA.md) | Sinkronisasi ER diagram, DDL `skpd`, dan pencatatan riwayat migrasi ke-11. |
| **[MODIFIED]** | [`penjelasan_lengkap.md`](file:///Ubuntu/home/jundan/sismon-tte-testing-V5/penjelasan_lengkap.md) | Penyelarasan dokumen komprehensif (header status, matriks masalah, DDL, ERD, dan rute). |

---

### 11.6 Hasil Verifikasi Pengujian di Lingkungan Docker
1. **Pengujian Otomatis (PHPUnit di Container `tte_app`):**
   - `docker exec tte_app php artisan test --filter=test_superadmin_dashboard_view_renders_successfully`
     $$\mathbf{PASS}\quad (\text{14 assertions, 0 failures})$$
   - `docker exec tte_app php artisan test --filter=can_manage_master_skpd`
     $$\mathbf{PASS}\quad (\text{5 assertions, 0 failures})$$
2. **Pengujian Visual & Interaksi UI Browser:**
   - Mode Default (`?sort=persentase`): Peringkat teratas dihuni oleh instansi dengan persentase kepatuhan tertinggi, progress bar terisi proporsional dengan warna sesuai capaian.
   - Mode Volume (`?sort=volume`): Peringkat teratas beralih menampilkan instansi dengan jumlah akumulasi pendaftar terbesar.


*Dokumen ini disusun sebagai catatan resmi perubahan sistem dan komparasi kode sumber untuk Dinas Komunikasi dan Informatika Kota Cirebon. Rincian komparasi diff baris-per-baris tersedia pada [`solusi_audit.md`](file:///f:/sismon-tte-testing-V4/solusi_audit.md).*



---

## BAB 12: IMPLEMENTASI MODUL ANALITIK CAPAIAN ADOPSI TTE SELURUH SKPD & EKSPOR CSV (V5.2.0 - SEPTEMBER 2026)

### 12.1 Latar Belakang & Identifikasi Kebutuhan Pengguna
Pada revisi sebelumnya (V5.1.0), widget "Top 10 SKPD" pada dashboard admin telah diperbarui sehingga menampilkan persentase pendaftar TTE terhadap populasi ASN (`total_asn`). Namun, timbul kendala operasional baru:
1. **Keterbatasan Visibilitas Top 10:** Administrator dan pimpinan Diskominfo hanya dapat melihat maksimal 10 instansi terbaik/terendah. Puluhan instansi lainnya di lingkungan Pemerintah Kota Cirebon tidak dapat dipantau perkembangannya melalui antarmuka admin.
2. **Ketiadaan Menu Analitik Menyeluruh:** Diperlukan satu menu khusus untuk melihat seluruh data SKPD aktif tanpa batasan paginasi sempit, lengkap dengan rekapitulasi rata-rata capaian tingkat kota.
3. **Kebutuhan Data Pelaporan (Ekspor CSV):** Untuk keperluan evaluasi kinerja berkala pimpinan daerah (Walikota/Sekda), diperlukan fungsi ekspor data capaian adopsi TTE berbasis persentase ke format CSV yang dapat diolah langsung menggunakan spreadsheet (Microsoft Excel).

### 12.2 Rincian Fitur yang Diimplementasikan
1. **Menu Baru: Capaian Adopsi SKPD (`/admin/capaian-skpd`)**:
   - Menu navigasi baru di sidebar pada grup *Dashboard* serta tautan cepat di grup *Pelaporan & Ekspor*.
   - Tombol pintas "Lihat Seluruh SKPD & Ekspor" pada widget Top 10 SKPD di Dashboard utama.
   - **4 Kartu Metrik KPI Eksekutif:**
     - *Rata-rata Capaian Kota (%)*
     - *Total Pegawai Terdaftar*
     - *Total Pendaftar TTE*
     - *Total Sertifikat Terbit*
   - **Tabel Lengkap & Interaktif:**
     - Menampilkan seluruh SKPD aktif dengan paginasi rapi (20 SKPD per halaman).
     - Kolom data: Peringkat/No, Nama & Singkatan SKPD, Total Pegawai, Total Pendaftar, Total Sertifikat Terbit, Progress Bar Persentase Capaian, dan Badge Kategori.
     - Pewarnaan Progress Bar otomatis:
       - 🟢 **Sangat Tinggi (≥75%)**: Emerald Green
       - 🔵 **Tinggi (50% - 74%)**: Blue Cyan
       - 🟡 **Sedang (25% - 49%)**: Amber
       - ⚪ **Rendah (<25%)**: Slate / Netral
   - **Filter & Sorting Lengkap:**
     - Pencarian teks instan untuk nama maupun singkatan SKPD (`?q=...`).
     - Filter kategori pencapaian (`?kategori=sangat_tinggi|tinggi|sedang|rendah`).
     - Pengurutan dinamis berdasarkan persentase capaian, jumlah pegawai, total pendaftar, total sertifikat, atau nama instansi (`?sort=...&dir=asc|desc`).

2. **Fitur Ekspor CSV Khusus Capaian Adopsi (`/admin/capaian-skpd/export`)**:
   - Streaming memory-safe via `response()->streamDownload()` dengan penanda UTF-8 Byte Order Mark (`\xEF\xBB\xBF`).
   - Format penamaan file otomatis: `capaian_adopsi_tte_skpd_YYYYMMDD_HHmmss.csv`.
   - Sinkronisasi filter aktif: data yang diekspor otomatis menyesuaikan pencarian dan filter kategori yang sedang dipilih oleh administrator.
   - Kolom ekspor: No, Nama SKPD, Singkatan, Total Pegawai, Total Pendaftar TTE, Total Sertifikat Terbit, Persentase Adopsi (%), Status Capaian.

### 12.3 Matriks Berkas yang Dimodifikasi pada Revisi V5.2.0

| Status Berkas | Path Berkas Kode | Ringkasan Perubahan |
| :--- | :--- | :--- |
| **[NEW]** | `app/Http/Controllers/CapaianSkpdController.php` | Controller analitik capaian adopsi TTE seluruh SKPD, kalkulasi KPI kota, filter, sorting, dan ekspor streaming CSV. |
| **[NEW]** | `resources/views/admin/capaian_skpd/index.blade.php` | Blade view Carbon Flat antarmuka analitik lengkap dengan KPI cards, filter bar, tabel dinamis, progress bar, & paginasi. |
| **[NEW]** | `tests/Feature/CapaianSkpdTest.php` | Suite pengujian lengkap dengan 12 test cases yang menguji render view, KPI, filter, sorting, ekspor CSV, dan proteksi auth. |
| **[MODIFIED]** | `routes/web.php` | Pendaftaran rute `admin.capaian-skpd.index` dan `admin.capaian-skpd.export` di bawah isolasi VPN + middleware auth. |
| **[MODIFIED]** | `resources/views/layouts/admin.blade.php` | Penambahan item navigasi sidebar "Capaian Adopsi SKPD" dan shortcut ekspor CSV di sub-menu pelaporan. |
| **[MODIFIED]** | `resources/views/admin/dashboard.blade.php` | Penambahan tombol pintas aksi "Lihat Seluruh SKPD & Ekspor" di header widget Top 10. |
| **[MODIFIED]** | `tests/Feature/FrontendViewRenderTest.php` | Penambahan assertion untuk memastikan view capaian SKPD ter-render sempurna bagi Superadmin (total 6 test pass). |

### 12.4 Hasil Verifikasi Pengujian Test Suite
1. **Uji Fitur Khusus (`CapaianSkpdTest`):**
   - 12 passed (48 assertions), 0 failures.
2. **Uji Render View (`FrontendViewRenderTest`):**
   - 6 passed (48 assertions), 0 failures.
3. **Uji Menyeluruh Sistem (Full Test Suite):**
   - **70 passed (351 assertions)** di seluruh suite unit dan feature testing.
