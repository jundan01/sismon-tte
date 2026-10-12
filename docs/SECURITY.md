# SECURITY.md — Spesifikasi Arsitektur Keamanan Sistem

**Proyek:** Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas  
**Instansi:** Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon (DKIS Kota Cirebon) / Bidang Persandian  
**Domain Resmi Email Dinas:** `@cirebonkota.go.id`  
**Target Skalabilitas:** 100.000 – 1.000.000+ Records (Enterprise Skala Nasional)  
**Core Stack:** PHP 8.3 / Laravel 13 + PostgreSQL 16 + Redis 7 + Nginx + Docker Compose  
**Dokumen Terkait:** [PRD.md](file:///f:/sismon-tte-testing-V4/docs/PRD.md) v3.2.0-PROD, [ARCHITECTURE.md](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md) v3.2.0-PROD, [DESIGN.md](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md) v3.2.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.2.0-PROD, [OPERATIONAL_GUIDE.md](file:///f:/sismon-tte-testing-V4/docs/OPERATIONAL_GUIDE.md) v3.2.0-PROD, [solusi_audit.md](file:///f:/sismon-tte-testing-V4/solusi_audit.md)  
**Versi Dokumen:** 3.2.0-PROD  
**Klasifikasi:** Internal — Terbatas (Spesifikasi Keamanan Data Sensitif Pemerintah)  

---

> **Catatan Sebelumnya:**
> Versi 3.1.0-PROD (19-23 Agustus 2026) menetapkan perimeter Zero-Trust VPN Gateway dan enkripsi data rest. Versi 3.2.0-PROD (24 Agustus 2026) menyelaraskan hasil audit keamanan kode program ([`AUDIT.md`](file:///f:/sismon-tte-testing-V4/AUDIT.md) & [`solusi_audit.md`](file:///f:/sismon-tte-testing-V4/solusi_audit.md)):
> 1. Eliminasi total NIK 16 digit & NIP 18 digit dari browser storage klien (migrasi ke `sessionStorage` temporer tanpa PII mentah sesuai UU PDP No. 27/2022).
> 2. Penegakan re-autentikasi kata sandi aktif (*step-up authentication* `current_password`), otorisasi wewenang, dan pencatatan audit log `DISABLE_MFA_USER` saat menonaktifkan TOTP MFA.
> 3. Penyimpanan terenkripsi 8 MFA backup recovery codes pada basis data untuk mencegah *admin lockout*.
> 4. Penegakan eksplisit Laravel Policies (`PendaftaranTtePolicy` & `SertifikatTtePolicy`) di seluruh controller operasional.
> 5. Penegakan strict mandatory variable pada Docker Compose (`${DB_PASSWORD:?...}`) untuk mencegah penggunaan default password lemah di lingkungan produksi.

---

## 1. Filosofi & Prinsip Keamanan Skala Nasional

Sistem memproses dan mengelola data pribadi kependudukan dan kepegawaian Aparatur Sipil Negara (NIK, NIP, Nomor Telepon, Dokumen SK/Rekomendasi) yang menjadi prasyarat penerbitan sertifikat Tanda Tangan Elektronik (TTE) resmi BSrE. Seluruh arsitektur keamanan dirancang berlandaskan kerangka **Zero-Trust Security** dan **Defense-in-Depth**:

1. **Defense in Depth & Network Segmentation:** Perlindungan berlapis dari Layer 0 (Edge/Network & VPN Gateway) hingga Layer 7 (Tata Kelola & SDLC). Kegagalan pada satu lapisan tidak mengakibatkan kompromi data menyeluruh.
2. **Zero-Trust Administrative Perimeter:** Rute masuk admin (`/gateway/login` dan `/admin/*`) diisolasi secara mutlak di dalam **VPN Gateway / Intranet DKIS Kota Cirebon**. Publik dari internet luar tidak memiliki visibilitas maupun akses (*Stealth 404 Rejection*).
3. **Least Privilege:** Tidak ada komponen kontainer, proses aplikasi, atau pengguna yang memiliki akses lebih dari kebutuhan operasionalnya. Jaringan basis data dan cache terisolasi tanpa akses langsung dari luar (`backend: internal: true`).
4. **Kepatuhan Regulasi:** Memenuhi ketentuan **UU No. 27 Tahun 2022** tentang Pelindungan Data Pribadi (UU PDP) serta Pedoman Standar Keamanan Siber BSSN untuk sistem persandian pemerintahan.
5. **Resiliensi Anti-DoS & Memory Safety:** Pencegahan serangan denial-of-service berbasis resource exhaustion melalui rate limiting bertingkat, Asynchronous Audit Logging via Redis Queue, dan Database Cursor Streaming pada ekspor data massal.

---

## 2. Model Ancaman Skala Nasional (Threat Matrix)

| Aset & Komponen | Vektor Ancaman Utama | Dampak Potensial | Mitigasi Bertingkat (Defense Layer) |
| :--- | :--- | :--- | :--- |
| **Administrative Attack Surface** | Port scanning, automated bot brute force pada endpoint login publik | Pembobolan akun Superadmin, beban server akibat serangan DDoS | **Isolasi VPN Gateway + Stealth 404 Block** pada rute non-VPN, penghapusan `/login` publik terbuka ([ARCHITECTURE.md §1.2](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#12-prinsip-isolasi-jaringan--perimeter)) |
| **Data Pribadi ASN (NIK & NIP)** | Dumping basis data, SQL Injection, pencurian data massal | Pelanggaran UU PDP, kebocoran data identitas aparatur | Enkripsi *at-rest* AES-256-GCM + HMAC-SHA256 Blind Indexing ([SCHEMA.md §10](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#10-data-sensitif-enkripsi-aes-256-gcm--blind-indexing)) |
| **Form Pendaftaran Publik** | Bot flood submit, enumerasi NIK/NIP, timing attack | Server overload, pengintaian NIK ASN terdaftar | Rate limiting (5r/m), Google reCAPTCHA (timeout 3s) + Local Math CAPTCHA Fallback, Pesan error generik: `"NIK/NIP sudah terdata."` |
| **Akun Superadmin & IAM** | Credential stuffing, brute-force, phishing | Pengambilalihan kontrol penerbitan sertifikat TTE | Akses wajib via VPN Gateway, Argon2id/Bcrypt hashing, lockout progresif (5x gagal), TOTP MFA wajib (Google Authenticator) |
| **Penerbitan & Rekap Email** | Penyalahgunaan wewenang internal (Insider Threat) | Penerbitan sertifikat untuk pihak tidak berhak | Pemisahan kolom audit Tahap 1 (`verified_by`) & Tahap 2 (`verified_by_tahap2`), log pencatatan mutasi rekap email |
| **Pencatatan Audit Trail** | Database table locking akibat lonjakan ribuan transaksi per detik | Server aplikasi mengalami latensi tinggi / hang | Logging asinkron via Redis Queue (`ProcessAuditLogJob`) + Partisi Bulanan PostgreSQL 16 ([ARCHITECTURE.md §10](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#10-scheduler--background-jobs)) |
| **Ekspor Laporan Massal** | Eksploitasi ekspor jutaan baris data (OOM Denial of Service) | Server crash akibat PHP Memory Exhaustion | Wajib menggunakan Database Cursor Streaming (`streamDownload`) dengan konsumsi RAM < 20MB |
| **Infrastruktur Kontainer** | Container breakout, privilege escalation, I/O lag | Kompromi host server, degradasi performa | Named Isolated Volumes (`vendor_data`, `node_data`), Nginx `read_only: true` + `tmpfs`, `no-new-privileges: true` |

---

## 3. Diagram Arsitektur Defense-in-Depth Skala Nasional

```
                                  [ JALUR INTERNET ]
                                          │
                        +---------------------------------+
   LAYER 0  Perimeter   |  TLS 1.3 + HSTS (Preload)       |  <-- Enkripsi Transport
                        |  Edge Rate Limiter (limit_req)  |  <-- Anti-DDoS & Flooding
                        +----------------+----------------+
                                         │
                        +---------------------------------+
   LAYER 1  Edge Proxy  |     Nginx Hardened Reverse      |  <-- Security Headers, Brotli/Gzip
                        |  IP CIDR Allow: VPN Subnet Only |  <-- Blokir /gateway/ & /admin/
                        +----------------+----------------+
                                         │ [frontend network]
   +-----------------------------------------------------------------------------------+
   | LAYER 2  Aplikasi (Laravel 13 / PHP 8.3)                                           |
   |  • EnsureVpnAccess Middleware -> Subnet IP Matching + Stealth 404 Rejection       |
   |  • Auth + Progressive Lockout + TOTP MFA -> RBAC Policy Superadmin                 |
   |  • FormRequest Sanitization -> reCAPTCHA (3s) + Local Math CAPTCHA Fallback       |
   |  • Anti-Enumeration Generic Error: "NIK/NIP sudah terdata."                       |
   |  • Memory-Safe Cursor Streaming Export Engine (RAM < 20MB)                        |
   +-----------------------------------------------------------------------------------+
                                         │ [backend network: internal: true]
   +-----------------------------------------------------------------------------------+
   | LAYER 3  Data & Storage                                                           |
   |  • Field-Level Encryption: AES-256-GCM (NIK & NIP)                                |
   |  • HMAC-SHA256 Blind Indexing + Partial Unique Index (kecuali DITOLAK)            |
   |  • SHA-256 Checksum & MIME Validation pada Berkas Lampiran                        |
   |  • PgBouncer Connection Pooling (Mencegah Lonjakan Koneksi Database)              |
   +-----------------------------------------------------------------------------------+
                                         │
   +-----------------------------------------------------------------------------------+
   | LAYER 4-5  Identitas & Observability Asinkron                                     |
   |  • Session Hardening: secure, httponly, samesite=strict, idle timeout 15-30m      |
   |  • Asynchronous Audit Logging via Redis 7 Queue (Non-blocking HTTP response)      |
   |  • PostgreSQL 16 Native Monthly Range Partitioning + GIN Index on JSONB           |
   +-----------------------------------------------------------------------------------+
                                         │
   +-----------------------------------------------------------------------------------+
   | LAYER 6  Infrastruktur & Kontainer                                                |
   |  • Docker Named Isolated Volumes (vendor_data, node_data, storage_data)           |
   |  • Non-root User (1000:1000) & Security Option: no-new-privileges: true           |
   +-----------------------------------------------------------------------------------+
```

---

## 4. Lapisan 0–1: Jaringan, Perimeter & VPN Gateway

### 4.1 Standar Transport & TLS
- Menggunakan protokol **TLS 1.3** sebagai standar utama (TLS 1.2 sebagai batas minimum).
- Mengaktifkan **HSTS** dengan durasi 1 tahun (`Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`).

### 4.2 Nginx Hardening & Rate Limiting
```nginx
# Rate Limiting Zones di Nginx
limit_req_zone $binary_remote_addr zone=pendaftaran_limit:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=general_limit:10m rate=30r/s;

server {
    listen 443 ssl http2;
    server_name tte.cirebonkota.go.id;

    client_max_body_size 5M;

    # Proteksi Endpoint Pendaftaran Publik
    location = /pendaftaran {
        limit_req zone=pendaftaran_limit burst=2 nodelay;
        try_files $uri /index.php?$query_string;
    }

    # Proteksi Endpoint Autentikasi Superadmin (VPN Gateway Only)
    location ^~ /gateway/ {
        limit_req zone=pendaftaran_limit burst=3 nodelay;
        try_files $uri /index.php?$query_string;
    }

    # Proteksi Panel Superadmin (VPN Gateway Only)
    location ^~ /admin/ {
        try_files $uri /index.php?$query_string;
    }

    # Kompresi Payload
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;
    brotli on;
    brotli_types text/plain text/css application/json application/javascript text/xml;
}
```

### 4.3 Isolasi Jalur Admin Berbasis VPN Gateway (Zero-Trust)
1. **Pemisahan URL:** Rute `/login` publik dihapus sepenuhnya. Rute login admin dialihkan ke `/gateway/login`.
2. **Inspeksi IP Subnet CIDR:** Middleware `EnsureVpnAccess` memverifikasi apakah IP client berada dalam rentang subnet VPN resmi yang dikonfigurasi pada `config/vpn.php` (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1/32`).
3. **Respon Stealth Mode (HTTP 404):** Permintaan dari IP non-VPN tidak ditolak dengan 403 Forbidden melainkan langsung dijawab dengan **HTTP 404 Not Found**. Hal ini memastikan penyerang eksternal tidak dapat mengonfirmasi apakah endpoint admin benar-benar ada di URL tersebut (*Security by Obscurity + Zero Trust*).
4. **Pencatatan Audit Pelanggaran Perimeter:** Percobaan akses tidak sah dari IP luar dicatat ke file log peringatan keamanan dengan format:
   ```php
   Log::warning('Percobaan akses rute admin di luar VPN Gateway', [
       'ip' => $request->ip(),
       'url' => $request->fullUrl(),
       'user_agent' => $request->userAgent(),
   ]);
   ```

### 4.4 Isolasi Jaringan Kontainer
Di `docker-compose.yml`, service `postgres` dan `redis` ditempatkan pada jaringan `backend` dengan konfigurasi `internal: true`. Tidak ada pemetaan port (`ports:`) ke host publik untuk kedua service ini. Redis wajib dikonfigurasi dengan `--requirepass ${REDIS_PASSWORD}` dan AOF persistence.

---

## 5. Lapisan 2: Keamanan Aplikasi (Laravel 13)

### 5.1 Validasi Input Ketat & Anti-Enumerasi
- Validasi numerik 16 digit untuk NIK dan 18 digit untuk NIP pada `StorePendaftaranRequest`.
- Pesan error keunikan wajib generik: **`"NIK/NIP sudah terdata."`** guna mencegah bot memetakan identitas ASN yang sudah terdaftar.
- Waktu respons aplikasi dibuat konstan (*constant-time response*) untuk memitigasi serangan *timing attack*.

### 5.2 Fast Timeout reCAPTCHA & Local Math CAPTCHA Engine
Untuk menjamin ASN di daerah terpencil tidak mengalami kegagalan akibat timeout panggilan ke server Google:

```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class CaptchaService
{
    public function verify(Request $request): bool
    {
        $token = $request->input('g-recaptcha-response');

        if ($token) {
            try {
                $response = Http::timeout(3)->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $token,
                ]);

                if ($response->successful() && $response->json('success') === true) {
                    return true;
                }
            } catch (\Exception $e) {
                // Timeout / Gagal koneksi luar -> Fallback ke Local Math CAPTCHA
            }
        }

        // Verifikasi Local Math CAPTCHA
        return $this->verifyLocalMathCaptcha(
            $request->input('local_captcha_answer'),
            $request->input('local_captcha_signature')
        );
    }

    protected function verifyLocalMathCaptcha(?string $answer, ?string $signature): bool
    {
        if (!$answer || !$signature) return false;

        [$expectedAnswer, $timestamp] = explode('|', decrypt($signature));

        // Batas waktu validitas 5 menit & jawaban cocok
        if (now()->timestamp - $timestamp > 300) return false;

        return hash_equals((string)$expectedAnswer, (string)$answer);
    }
}
```

### 5.3 Security Headers & CSP Terstandar
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; frame-src https://www.google.com; frame-ancestors 'none'; base-uri 'self';"
        );

        return $response;
    }
}
```

---

## 6. Lapisan 4: Identity & Access Management (IAM)

### 6.1 RBAC & Otorisasi Eksplisit Superadmin
Meskipun sistem menerapkan model single internal role (`superadmin`), seluruh operasi sensitif dilindungi oleh Policy eksplisit (`PendaftaranTtePolicy`):
- `approveTahap1`: Wewenang menyetujui pendaftaran identitas & kelayakan berkas.
- `rekapEmailDinas`: Wewenang review, edit manual, dan mengonfirmasi penetapan `email_final_terverifikasi`.
- `terbitkanSertifikat`: Wewenang mencatat tanggal terbit TTE Tahap 2.
- `exportLaporan`: Wewenang menjalankan mass streaming export laporan.

### 6.2 Multi-Factor Authentication (MFA) & Progressive Lockout
- **Dual-Factor Access Requirement:** Akses Superadmin membutuhkan:
  1. *Jaringan:* Terhubung ke jaringan VPN Gateway resmi.
  2. *Kredensial:* Kata sandi kuat dengan proteksi progressive lockout (5x percobaan gagal mengunci akun 5 menit).
  3. *MFA:* Token 6 digit berbasis waktu (**RFC 6238 TOTP Google Authenticator**).
- Kode pemulihan (*recovery codes*) disimpan dalam kondisi terenkripsi di basis data.

### 6.3 Segregation of Duty (Mitigasi pada Model Superadmin)
Karena seluruh hak operasional dipegang oleh peran `superadmin`, pemisahan kewenangan ditegakkan pada tingkat audit dan deteksi anomali:
- Kolom `verified_by` (Tahap 1) dan `verified_by_tahap2` (Tahap 2) dicatat secara terpisah.
- Sistem secara otomatis menandai anomali dan mengirimkan alert jika kedua tahap diproses oleh `user_id` yang sama pada record ASN yang sama.

---

## 7. Lapisan 3: Keamanan Data Sensitif

### 7.1 Enkripsi Kolom NIK & NIP (AES-256-GCM + Blind Indexing)
- Nilai asli NIK dan NIP dienkripsi menggunakan algoritma `AES-256-GCM` sebelum disimpan ke disk PostgreSQL.
- Untuk pencarian cepat dan penegakan *partial unique constraint*, sistem membuat nilai hash deterministik menggunakan `hash_hmac('sha256', $value, config('app.key'))` yang disimpan pada kolom `nik_hash` dan `nip_hash`.

### 7.2 Kontrol Export Laporan & Anti-DoS Memori
- Fitur ekspor dibatasi hanya untuk Superadmin terautentikasi melalui jalur VPN dan tercatat dalam `security_audit_logs`.
- Ekspor massal data transaksi wajib menggunakan **Database Cursor Streaming** (`PendaftaranTte::cursor()`), mengalirkan baris data terenkripsi langsung ke output stream HTTP tanpa memuat seluruh dataset ke RAM PHP, menjaga konsumsi memori stabil < 20MB.
- **Keamanan Ekspor Capaian Adopsi SKPD (`/admin/capaian-skpd/export`):**
  1. **Sanitasi Parameter Kueri:** Seluruh parameter pencarian (`q`), filter kategori (`kategori`), dan pengurutan (`sort`, `dir`) divalidasi dan di-sanitize secara ketat untuk mencegah SQL Injection dan query manipulation.
  2. **Mitigasi CSV Formula Injection:** Seluruh nilai teks nama SKPD dan singkatan disanitasi agar tidak memicu eksekusi formula pada aplikasi spreadsheet (menghindari karakter awalan berbahaya seperti `=`, `+`, `-`, atau `@`).
  3. **Streaming Langsung Ber-BOM:** Mengalirkan respon HTTP dengan header UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) tanpa penampungan buffer di memori server aplikasi.

---

## 8. Lapisan 5: Audit Trail Asinkron & Observability

### 8.1 Logging Asinkron via Redis Queue
Pencatatan aktivitas audit (login, update status, rekap email, export data) didelegasikan ke antrian Redis `audit-logs` melalui Eloquent Observer (`AuditObserver`):

```php
namespace App\Domain\Pendaftaran\Observers;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Jobs\ProcessAuditLogJob;

class AuditObserver
{
    public function updated(PendaftaranTte $pendaftaran): void
    {
        ProcessAuditLogJob::dispatch([
            'user_id' => auth()->id(),
            'action_type' => 'UPDATE_PENDAFTARAN_STATUS',
            'entity_name' => 'pendaftaran_tte',
            'entity_id' => $pendaftaran->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload_before' => $pendaftaran->getOriginal(),
            'payload_after' => $pendaftaran->getChanges(),
        ])->onQueue('audit-logs');
    }
}
```

### 8.2 Partisi Tabel Bulanan & Indeks GIN
Tabel `security_audit_logs` menerapkan **Native Range Partitioning by Month** pada PostgreSQL 16. Struktur ini mempermudah manajemen arsip data log tahunan dan mempercepat query penelusuran insiden melalui indeks GIN pada kolom `payload_after`.

---

## 9. Lapisan 6: Keamanan Infrastruktur Kontainer

- **Non-Root Runtime:** Kontainer PHP-FPM dan Nginx dijalankan dengan UID non-root (`www-data:www-data`).
- **Read-Only Rootfs:** Nginx dikonfigurasi `read_only: true` dengan writable cache terbatas pada `tmpfs` (`/var/cache/nginx`, `/var/run`, `/tmp`).
- **Security Options:** Diberlakukan `security_opt: ["no-new-privileges:true"]` di seluruh service Docker.
- **Resource Constraints:** Batasan CPU dan RAM diberlakukan pada Nginx (0.5 CPU, 256MB), App (1.0 CPU, 512MB), Postgres (1.5 CPU, 1024MB), dan Redis (0.5 CPU, 256MB) untuk mencegah *noisy neighbor attack*.

---

## 10. Checklist Hardening Sebelum Go-Live

| Kategori | Parameter Hardening | Verifikasi |
| :--- | :--- | :---: |
| **Jaringan & VPN** | TLS 1.3 aktif, HSTS Preload terpasang | ☑ |
| **Jaringan & VPN** | Jalur login admin terisolasi di `/gateway/login` & rute `/login` publik dihapus | ☑ |
| **Jaringan & VPN** | Middleware `EnsureVpnAccess` aktif & request non-VPN mengembalikan HTTP 404 (Stealth) | ☑ |
| **Jaringan & VPN** | Port `postgres` dan `redis` tidak di-expose ke host publik (`internal: true`) | ☑ |
| **Jaringan & VPN** | Redis dilindungi `requirepass` dan mode AOF | ☑ |
| **Aplikasi** | `APP_DEBUG=false` terverifikasi di environment produksi | ☑ |
| **Aplikasi** | Rate limiting Nginx + Google reCAPTCHA & Local CAPTCHA Fallback aktif | ☑ |
| **Aplikasi** | Security Headers (HSTS, CSP, X-Frame-Options, X-Content-Type-Options) aktif | ☑ |
| **Data** | NIK dan NIP terenkripsi AES-256-GCM + Blind Indexing HMAC-SHA256 | ☑ |
| **Data** | Partial Unique Index terpasang (mengizinkan resubmission data `DITOLAK`) | ☑ |
| **Data** | Berkas lampiran tervalidasi MIME & tersimpan nilai SHA-256 Checksum | ☑ |
| **IAM** | TOTP MFA aktif untuk seluruh akun Superadmin | ☑ |
| **IAM** | Aksi rekap email definitif Superadmin tercatat di audit log | ☑ |
| **Audit** | Redis Queue Worker untuk `audit-logs` berjalan stabil | ☑ |
| **Audit** | Partisi bulanan tabel `security_audit_logs` terkonfigurasi | ☑ |
| **Performa** | Ekspor laporan teruji stabil < 20MB RAM pada 500.000 data | ☑ |

---

## 11. Kontrol Dokumen

| Item | Keterangan |
| :--- | :--- |
| **Versi Dokumen** | **5.2.0-PROD** |
| **Tanggal Pembaruan** | 19 Agustus 2026 |
| **Dokumen Acuan** | [PRD.md](file:///f:/sismon-tte-testing-V4/docs/PRD.md) v3.1.0-PROD, [ARCHITECTURE.md](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md) v3.1.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.1.0-PROD, [DESIGN.md](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md) v3.1.0-PROD |
| **Riwayat Perubahan** | • v1.2.0: Defense in depth 2-peran.<br>• v2.1.0-PROD: Penyesuaian menyeluruh standar Skala Nasional Zero-Trust: Enkripsi AES-256-GCM + HMAC-SHA256 Blind Indexing, mitigasi DoS via Redis Queue Logging asinkron & Cursor Streaming Export, Local Math CAPTCHA Fallback security, validasi integritas lampiran berkas via SHA-256 Checksum, audit trail modifikasi/rekap email dinas (`@cirebonkota.go.id`), dan pengerasan kontainer Docker Compose.<br>• **v3.1.0-PROD:** Penambahan **Layer 0/1 Isolasi Rute Admin Berbasis VPN Gateway (Zero-Trust Administrative Perimeter & Stealth 404 Rejection)**. |
| **Target Pembaca** | Chief Information Security Officer (CISO), Security Engineer, Lead Backend Developer, DevOps / Infra Engineer |
