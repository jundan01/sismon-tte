# DOKUMEN PERENCANAAN UPGRADE SISTEM MONITORING TTE V3
## UPGRADE LARAVEL 13 & ISOLASI JALUR ADMIN BERBASIS VPN GATEWAY

**Proyek:** Sistem Monitoring TTE & Pelayanan Email Dinas (`@cirebonkota.go.id`)  
**Instansi:** Dinas Komunikasi dan Informatika / Bidang Persandian Kota Cirebon  
**Versi Target:** 3.1.0-ENTERPRISE (Laravel 13 Ready + Zero-Trust VPN Gateway)  
**Dokumen:** `rencana_upgrade-v3.md`  
**Status:** DRAFT PROPOSAL ARSITEKTUR & RENCANA KERJA  

---

## 1. Ringkasan Eksekutif & Latar Belakang

Sistem Monitoring TTE Versi 3 saat ini berjalan pada fondasi Laravel 12 (`^12.0`). Dalam rangka standardisasi arsitektur enterprise skala nasional, peningkatan performa jangka panjang, serta penguatan perimeter keamanan siber (Zero Trust Architecture), sistem akan ditingkatkan dengan 2 (dua) sasaran utama:

1. **Upgrade Stack Framework ke Laravel 13:**  
   Menyelaraskan dependensi `composer.json`, runtime PHP, container environment, dan konfigurasi bootstrap dengan spesifikasi Laravel 13.
2. **Pemisahan Jalur Akses Admin dengan VPN Gateway (Network Segmentation & Stealth Perimeter):**  
   Mengeliminasi celah di mana publik dan superadmin sebelumnya berbagi entry point login di rute terbuka (`/login` dan `/admin/*`). Seluruh pintu masuk autentikasi dan panel kendali admin dipindahkan ke dalam perimeter terisolasi yang hanya dapat diakses melalui jalur **VPN Gateway / Intranet Pemerintah**. Permintaan dari jaringan publik ke rute admin akan menghasilkan respon *Stealth Mode* (HTTP 404 Not Found / 403 Forbidden), sehingga surface attack berkurang drastis.

---

## 2. Analisis Arsitektur & Kondisi Saat Ini (Current State)

```
[ KONDISI SAAT INI (V3.0 - Shared Public Route) ]

   Internet Publik ───────────────────────────────┐
   (Masyarakat / Pemohon ASN)                     │
         │                                        │
         ▼                                        ▼
   ┌──────────────────────────────────────────────────────────────┐
   │                    NGINX EDGE PROXY (Port 80/443)            │
   └──────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
   ┌──────────────────────────────────────────────────────────────┐
   │                     LARAVEL APPLICATION                      │
   │                                                              │
   │  • GET  /                       (Form Pendaftaran Publik)    │
   │  • POST /pendaftaran            (Store Pendaftaran)          │
   │  • GET  /login                  (Form Login Admin - TERBUKA) │ ⚠️ Celah Recon &
   │  • POST /login                  (Auth Admin - TERBUKA)       │ ⚠️ Brute Force
   │  • GET  /admin/*                (Panel Admin - Protected)   │
   └──────────────────────────────────────────────────────────────┘
```

### Identifikasi Isu Keamanan Jalur Bersama:
1. **Administrative Surface Exposure:** Siapapun di internet publik dapat mengakses endpoint `/login` dan mengetahui sistem administrasi yang berjalan.
2. **Resiko DDoS & Brute-Force pada Auth:** Meskipun dilindungi oleh *Progressive Lockout* dan *Rate Limiter*, endpoint publik tetap membebani CPU dan memori server saat menerima serangan masif.
3. **Kepatuhan Standar CSIRT / BSSN:** Standar keamanan aplikasi layanan kepemerintahan merekomendasikan pemisahan mutlak jalur manajemen internal dari akses publik.

---

## 3. Desain Arsitektur Baru: Target State (Laravel 13 + VPN Gateway)

```
[ KONDISI TARGET (V3.1 - Zero Trust VPN Gateway Segmentation) ]

      [ INTERNET PUBLIK ]                     [ INTRANET / DISKOMINFO VPN ]
   (Pemohon Layanan TTE ASN)                    (Superadmin / Operator Sandi)
              │                                                │
              │                                                ▼
              │                                   ┌───────────────────────────┐
              │                                   │    SECURE VPN GATEWAY     │
              │                                   │   (Subnet 10.x / 172.x)   │
              │                                   └────────────┬──────────────┘
              │                                                │
              ▼                                                ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                       NGINX REVERSE PROXY HARDENED                       │
   │                                                                          │
   │  • /                     ──► Terbuka untuk Publik (Pendaftaran Mandiri)  │
   │  • /pendaftaran          ──► Terbuka untuk Publik (Submit & Captcha)     │
   │  • /gateway/login        ──► HANYA VPN / Internal IP (Block Public 404)  │
   │  • /admin/*              ──► HANYA VPN / Internal IP (Block Public 404)  │
   └──────────────────────────────────────┬───────────────────────────────────┘
                                          │
                                          ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                LARAVEL 13 RUNTIME & APPLICATION PIPELINE                 │
   │                                                                          │
   │  [ Public Pipeline ]                 [ VPN Secured Pipeline ]            │
   │  ├── SecurityHeaders                 ├── EnsureVpnAccess (Middleware)   │
   │  ├── SanitizeInput                   ├── Progressive Lockout             │
   │  └── RateLimiter                     ├── TOTP MFA Multi-Factor           │
   │                                      └── Admin RBAC & Audit Log          │
   └──────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Rincian Rencana Implementasi

### Bagian A: Upgrade Framework ke Laravel 13

1. **Pembaruan `composer.json`:**
   - Ubah `"laravel/framework": "^12.0"` menjadi `"laravel/framework": "^13.0"`.
   - Pastikan requirement PHP kompatibel (`^8.2|^8.3` atau `^8.3`).
   - Periksa dan sesuaikan dependensi pendukung (`laravel/tinker`, `laravel/pail`, `laravel/pint`, `phpunit/phpunit ^11.5|^12.0`, `nunomaduro/collision`).
   - Jalankan `composer update --with-all-dependencies` atau verifikasi manifest dependency.

2. **Penyesuaian Bootstrap & Konfigurasi Runtime (`bootstrap/app.php`):**
   - Pastikan routing middleware, custom exception renderer, dan handler kompatibel 100% dengan pipeline middleware Laravel 13.
   - Pendaftaran middleware baru `EnsureVpnAccess` ke dalam group alias middleware.

3. **Verifikasi Dockerfile & Environment:**
   - Periksa `docker/php/Dockerfile` (PHP 8.3-fpm Alpine dengan ekstensi `pdo_pgsql`, `redis`, `gd`, `zip`, `opcache`, `bcmath`).
   - Pastikan image runtime siap menjalankan Laravel 13 dengan optimal.

---

### Bagian B: Isolasi Jalur Masuk Admin & VPN Gateway

1. **Penyusunan Konfigurasi VPN (`config/vpn.php` & `.env`):**
   - Buat file konfigurasi terpusat `config/vpn.php` yang mendukung:
     - `enabled`: Toggle proteksi VPN (true/false, default `true` pada staging/production).
     - `allowed_subnets`: Daftar CIDR Subnet IP VPN yang diizinkan (contoh: `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1/32` untuk local dev).
     - `gateway_header`: Header khusus opsional dari VPN reverse proxy (misal: `X-VPN-Gateway-Secret` atau `X-Internal-Gateway`).
     - `gateway_secret`: Secret token yang divalidasi jika menggunakan header gateway.
     - `response_mode`: Respon bila akses ditolak (`404` untuk stealth mode, `403` untuk forbidden).

2. **Pembuatan Middleware Proteksi VPN (`app/Http/Middleware/EnsureVpnAccess.php`):**
   - Menginspeksi IP client (`$request->ip()`) atau IP yang diteruskan via reverse proxy terpercaya (`$request->getClientIp()`).
   - Melakukan validasi IP terhadap CIDR Subnet menggunakan algoritma bitwise IP matching (mendukung IPv4 & IPv6).
   - Memvalidasi token/secret header jika dikonfigurasi.
   - Jika lolos validasi: Request diteruskan ke Controller.
   - Jika gagal validasi:
     - Catat upaya akses tidak sah ke security log audit trail (`Log::warning('Unauthorized access attempt to VPN route', ...)`).
     - Mengembalikan respon HTTP 404 (Not Found) untuk menyembunyikan eksistensi portal admin, atau respon JSON 404 untuk request API.

3. **Restrukturisasi Routing & Pemisahan Endpoint (`routes/web.php`):**
   - **Hapus / Alihkan Route `/login` Publik:**
     - Endpoint `/login` publik dihapus agar tidak menjadi titik serang.
   - **Buat Route Group Khusus VPN Gateway:**
     - Rute autentikasi admin dialihkan ke jalur terproteksi, contoh:
       - `GET  /gateway/login` (Halaman login khusus yang dilindungi VPN)
       - `POST /gateway/login` (Proses autentikasi dengan rate limiter + lockout)
       - `POST /gateway/logout` (Proses logout admin)
     - Rute panel kontrol admin:
       - `Route::middleware(['web', 'vpn.gateway', 'auth'])->prefix('admin')->group(...)`
   - Semua rute publik (`/`, `/pendaftaran`, `/pendaftaran/captcha/refresh`) tetap bersih dan hanya berfokus pada layanan pemohon.

4. **Penyesuaian Controller Autentikasi (`app/Http/Controllers/Auth/AuthController.php`):**
   - Update method redirect pasca-logout dan sesi kedaluwarsa untuk mengarah ke route login VPN (`gateway.login`) alih-alih route publik.
   - Menambahkan metadata `is_vpn_connection` dan IP VPN pada tabel `users` / log aktivitas login.

5. **Penyesuaian View & Layouts (`resources/views/layouts/admin.blade.php`, `resources/views/auth/login.blade.php`):**
   - Update action form login mengarah ke rute baru `gateway.login`.
   - Update tombol logout pada header admin dashboard mengarah ke `gateway.logout`.
   - Tambahkan badge indikator status jaringan pada panel header Superadmin: `[● JARINGAN VPN TERVERIFIKASI]`.

6. **Hardening Layer-1 pada NGINX Reverse Proxy (`docker/nginx/nginx.conf`):**
   - Tambahkan directive `geo` atau `allow`/`deny` pada blok lokasi `/admin` dan `/gateway/` di Nginx.
   - Request non-VPN yang mencapai Nginx dapat langsung diputus di edge sebelum menyentuh PHP-FPM, menghemat sumber daya komputasi secara maksimal.

---

## 5. Matriks Perubahan Berkas (File Impact Assessment)

| Tipe Aksi | Berkas Target | Deskripsi Perubahan |
| :--- | :--- | :--- |
| **[MODIFY]** | `composer.json` | Upgrade `"laravel/framework": "^13.0"` dan dependensi terkait |
| **[MODIFY]** | `.env.example` & `.env` | Penambahan variabel `VPN_GATEWAY_ENABLED`, `VPN_ALLOWED_SUBNETS`, dll. |
| **[NEW]** | `config/vpn.php` | Konfigurasi terpusat subnet CIDR, header secret, dan respon mode VPN |
| **[NEW]** | `app/Http/Middleware/EnsureVpnAccess.php` | Middleware inspeksi IP Subnet CIDR & Header Gateway |
| **[MODIFY]** | `bootstrap/app.php` | Pendaftaran alias `vpn.gateway` dan mapping middleware |
| **[MODIFY]** | `routes/web.php` | Pemisahan rute auth admin ke `/gateway/login` dengan middleware `vpn.gateway` |
| **[MODIFY]** | `app/Http/Controllers/Auth/AuthController.php` | Penyesuaian redirect route login/logout & audit IP VPN |
| **[MODIFY]** | `resources/views/auth/login.blade.php` | Penyesuaian endpoint form & styling indikator VPN Gateway |
| **[MODIFY]** | `resources/views/layouts/admin.blade.php` | Penyesuaian form logout & indikator status koneksi VPN |
| **[MODIFY]** | `docker/nginx/nginx.conf` | Hardening limit req dan network access restriction untuk admin |
| **[MODIFY]** | `tests/Feature/AuthenticationTest.php` | Update skenario testing auth via VPN IP vs blocking Public IP |
| **[NEW]** | `tests/Feature/VpnGatewaySecurityTest.php` | Unit & Feature testing khusus untuk verifikasi blokir non-VPN |
| **[MODIFY]** | `Progres/progres.md` & `Progres/task.md` | Pencatatan changelog resmi versi 3.1.0 |

---

## 6. Skenario Pengujian & Verifikasi (Testing Matrix)

```
+-----------------------------------------------------------------------------------------+
|                                SKENARIO PENGUJIAN KEAMANAN                              |
+------------------------------------+-------------------------+--------------------------+
| Skenario Uji                       | Alamat IP Klien         | Ekspektasi Respon Sistem |
+------------------------------------+-------------------------+--------------------------+
| 1. Akses Portal Publik             | 202.67.x.x (Publik)     | 200 OK (Render Form)     |
| 2. Submit Pendaftaran Publik       | 202.67.x.x (Publik)     | 200/201 OK (Tersimpan)   |
| 3. Akses /gateway/login (Publik)   | 202.67.x.x (Publik)     | 404 Not Found (Stealth)  |
| 4. Akses /admin/dashboard (Publik) | 202.67.x.x (Publik)     | 404 Not Found (Stealth)  |
| 5. Akses /gateway/login (VPN)      | 10.10.1.50 (VPN Subnet) | 200 OK (Form Login VPN)  |
| 6. Login Kredensial Valid (VPN)    | 10.10.1.50 (VPN Subnet) | 200 OK (Redirect Admin)  |
| 7. Akses /admin/dashboard (VPN)    | 10.10.1.50 (VPN Subnet) | 200 OK (Dashboard Admin) |
| 8. Regression Test (38+ Tests)     | Local Test Runner       | 100% Passed Green        |
+------------------------------------+-------------------------+--------------------------+
```

---

## 7. Rencana Eksekusi Bertahap

- [x] **Langkah 1:** Pembuatan dokumen perencanaan `rencana_upgrade-v3.md` dan `implementation_plan.md` *(Sedang Berjalan)*.
- [x] **Langkah 2:** Update `composer.json` ke Laravel 13 dan sinkronisasi dependensi.
- [x] **Langkah 3:** Implementasi `config/vpn.php` dan Middleware `EnsureVpnAccess.php`.
- [x] **Langkah 4:** Restrukturisasi `routes/web.php`, `AuthController.php`, dan views terkait.
- [x] **Langkah 5:** Hardening konfigurasi `docker/nginx/nginx.conf`.
- [x] **Langkah 6:** Pembuatan test suite `VpnGatewaySecurityTest.php` dan penyesuaian test lama.
- [x] **Langkah 7:** Pengujian menyeluruh (Automated Tests & Syntax Validation).
- [x] **Langkah 8:** Pembaruan laporan progres di `Progres/progres.md` & `Progres/task.md`.

---
*Dokumen ini disusun untuk Dinas Komunikasi dan Informatika Kota Cirebon sebagai acuan resmi peningkatan sistem.*


---

## 8. Catatan Integrasi V5.1 & V5.2 (September 2026)

Menindaklanjuti keberhasilan upgrade arsitektur Laravel 13 dan isolasi perimeter VPN Gateway:
1. **Rilis V5.1 (Metrik Kepatuhan Top 10 SKPD Berbasis Populasi ASN):**
   - Penambahan kolom `total_asn` pada tabel `skpd`.
   - Pembaruan ranking dashboard berbasis persentase adopsi TTE dilengkapi toggle mode sorting dan indikator progress bar berwarna.
2. **Rilis V5.2 (Modul Analitik Capaian Adopsi TTE Seluruh SKPD & Ekspor CSV):**
   - Pembuatan modul analitik penuh `/admin/capaian-skpd` dengan tabel seluruh SKPD aktif, 4 kartu KPI kota, filter pencarian & kategori, dan pengurutan dinamis.
   - Pembuatan endpoint ekspor CSV streaming ber-BOM `/admin/capaian-skpd/export`.
   - Perluasan test suite dengan 12 skenario pengujian pada `CapaianSkpdTest.php` (total 70 tests passed, 351 assertions).
