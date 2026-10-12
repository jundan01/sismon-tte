# ARCHITECTURE.md — Spesifikasi Arsitektur Sistem

**Proyek:** Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas  
**Instansi:** Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon (DKIS Kota Cirebon) / Bidang Persandian  
**Domain Resmi Email Dinas:** `@cirebonkota.go.id`  
**Target Skalabilitas:** 100.000 – 1.000.000+ Records (Enterprise Skala Nasional)  
**Core Stack:** PHP 8.3 / Laravel 13 + PostgreSQL 16 + Redis 7 + Nginx + Docker Compose  
**Dokumen Terkait:** [PRD.md](file:///f:/sismon-tte-testing-V4/docs/PRD.md) v3.2.0-PROD, [DESIGN.md](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md) v3.2.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.2.0-PROD, [SECURITY.md](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md) v3.2.0-PROD, [OPERATIONAL_GUIDE.md](file:///f:/sismon-tte-testing-V4/docs/OPERATIONAL_GUIDE.md) v3.2.0-PROD, [solusi_audit.md](file:///f:/sismon-tte-testing-V4/solusi_audit.md)  
**Versi Dokumen:** 3.2.0-PROD  
**Status:** Ditetapkan sebagai Spesifikasi Arsitektur Final Skala Nasional (Laravel 13 + Zero-Trust VPN Gateway + Post-Audit Hardened)  

---

> **Catatan Sebelumnya:**
> Versi 3.1.0-PROD (19-23 Agustus 2026) menetapkan arsitektur monolit modular dengan segmentasi VPN Gateway. Versi 3.2.0-PROD (24 Agustus 2026) menambahkan penyempurnaan menyeluruh hasil audit kode program ([`AUDIT.md`](file:///f:/sismon-tte-testing-V4/AUDIT.md) & [`solusi_audit.md`](file:///f:/sismon-tte-testing-V4/solusi_audit.md)):
> 1. Kepatuhan privasi data ASN di browser klien: Pengalihan draft form ke `sessionStorage` dengan eksklusi total NIK/NIP mentah.
> 2. Penegakan otorisasi controller via Laravel Policies (`PendaftaranTtePolicy` & `SertifikatTtePolicy`).
> 3. Eliminasi badai kueri N+1 pada mass streaming export menggunakan `DB::table()->leftJoin()->cursor()` unbuffered FastCGI.
> 4. Ketahanan skema partisi log audit dengan default partition fallback.
> 5. Penegakan strict mandatory variable pada Docker Compose & unbuffered streaming di Nginx.

---

## 1. Ringkasan Arsitektur & Topologi Jaringan

Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas dibangun dengan arsitektur **Enterprise Monolithic Modular** berbasis **Laravel 13** dan **PHP 8.3**. Arsitektur ini dirancang untuk mencapai keandalan pemrosesan data bervolume tinggi (*High-Concurrency*), resiliensi koneksi daerah terpencil (*Low-Bandwidth Resilience*), serta pengamanan perimeter mutlak melalui **Segmentasi Jalur Admin berbasis VPN Gateway**.

### 1.1 Diagram Topologi Kontainer & Segmentasi Jaringan

```
   [ JALUR PUBLIK (INTERNET) ]              [ JALUR INTERNAL (DKIS VPN) ]
     (ASN Pemohon Layanan TTE)                  (Superadmin / Operator Sandi)
                │                                            │
                │                                            ▼
                │                               ┌───────────────────────────┐
                │                               │    SECURE VPN GATEWAY     │
                │                               │   (Subnet 10.x / 172.x)   │
                │                               └────────────┬──────────────┘
                │                                            │
                ▼                                            ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                    NGINX EDGE REVERSE PROXY (Port 80/443)                │
   │    - TLS 1.3 / HSTS / Strict Security Headers                            │
   │    - Brotli & Gzip Payload Compression                                   │
   │    - Network Rate Limiting (limit_req)                                   │
   │    - Edge IP CIDR Whitelist (/gateway/ & /admin/ -> VPN Subnet Only)     │
   │    - Read-Only Rootfs + tmpfs mounts                                     │
   └────────────────────────────────────┬─────────────────────────────────────┘
                                        │  [Network: frontend]
                                        ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                  APP: LARAVEL 13 (PHP 8.3-FPM RUNTIME)                   │
   │                                                                          │
   │  [ Public Pipeline ]                 [ VPN Secured Pipeline ]            │
   │  ├── GET  / (Form Pendaftaran)       ├── EnsureVpnAccess (Middleware)   │
   │  ├── POST /pendaftaran               ├── GET/POST /gateway/login         │
   │  └── Local CAPTCHA Fallback          ├── Progressive Lockout + TOTP MFA  │
   │                                      └── /admin/* (Dashboard & CRUD)     │
   │                                                                          │
   │  - Isolated Named Volumes (vendor_data, node_data, storage_data)         │
   │  - Zero External CDN Local Assets                                        │
   │  - Field-Level Encryption (AES-256-GCM) + HMAC-SHA256 Blind Index        │
   │  - Cursor Streaming Mass Export Engine (RAM < 20MB)                      │
   └────────────────────────────┬─────────────────────────────┬───────────────┘
                                │                             │
         [Network: backend]     │                             │  [Network: backend]
         (internal: true)       │                             │  (internal: true)
                                ▼                             ▼
   ┌──────────────────────────────────────────┐  ┌────────────────────────────┐
   │    POSTGRESQL 16 ENTERPRISE ENGINE       │  │  REDIS 7 IN-MEMORY BUFFER  │
   │     - PgBouncer Connection Pooling       │  │   - High-Speed Cache       │
   │     - Native Monthly Partition Logs      │  │   - Async Queue: audit-logs│
   │     - Partial Unique Indexing            │  │   - AOF & Requirepass      │
   └──────────────────────────────────────────┘  └────────────────────────────┘
```

### 1.2 Prinsip Isolasi Jaringan & Perimeter
1. **Jaringan Frontend (`frontend`):** Bridge network yang menghubungkan Nginx ke kontainer aplikasi Laravel (`app`).
2. **Jaringan Backend (`backend`, `internal: true`):** Jaringan terisolasi penuh tanpa rute ke internet publik. Kontainer `postgres` dan `redis` hanya dapat diakses oleh kontainer `app`, menutup risiko eksploitasi port langsung dari luar.
3. **Isolasi Jalur Admin (VPN Gateway & Stealth Defense):**
   - Rute autentikasi admin `/login` publik dihapus.
   - Pintu masuk Superadmin dipindahkan ke `/gateway/login` dan `/admin/*` yang di-guard oleh middleware `EnsureVpnAccess` dan pembatasan subnet pada Nginx.
   - Setiap permintaan yang berasal dari luar subnet VPN yang diizinkan (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1/32`) akan langsung dijawab dengan respon **HTTP 404 Not Found (Stealth Mode)**, menyembunyikan eksistensi antarmuka admin dari internet terbuka.

---

## 2. Peran & Aktor Sistem

Sistem menerapkan model 2 peran yang tegas dengan segmentasi perimeter fisik/jaringan:

| Peran | Saluran Akses & Autentikasi | Cakupan Akses & Tanggung Jawab |
| :--- | :--- | :--- |
| **Publik** (ASN Pendaftar) | **Jalur Internet Terbuka** (Tanpa Login, Portal Mandiri di `/`) | Mengisi formulir pendaftaran secara mandiri, memilih status email dinas, dan mengirimkan data tanpa beban unggah file. Terlindungi oleh draft auto-save lokal (`localStorage`) dan local CAPTCHA fallback. Tidak memiliki akses atau visibilitas ke rute admin ([PRD.md FR-01](file:///f:/sismon-tte-testing-V4/docs/PRD.md#71-modul-pendaftaran-publik--resiliensi-pelosok)). |
| **Superadmin** | **Jalur VPN Gateway Khusus** (Wajib Terkoneksi VPN + Login Kredensial di `/gateway/login` + TOTP MFA) | Memegang wewenang operasional penuh: verifikasi data Tahap 1, review & edit usulan email serta penekanan tombol **"Rekap Email"** definitif, input tanggal terbit sertifikat Tahap 2, pemantauan Dashboard & H-3 Expired, manajemen master data (SKPD, Jabatan, Golongan, Akun), serta eksekusi streaming mass export. |

---

## 3. Pilihan Teknologi & Spesifikasi Stack

| Komponen | Pilihan Teknologi | Peran & Rationale Skala Nasional |
| :--- | :--- | :--- |
| **Bahasa & Framework** | PHP 8.3 / Laravel 13 | *Modern enterprise runtime*, dukungan *type safety*, performa FPM optimal, isolasi rute middleware, dan *native background jobs*. |
| **Basis Data Utama** | PostgreSQL 16 Alpine | Dukungan tipe `BIGINT GENERATED ALWAYS AS IDENTITY`, kolom JSONB dengan indeks GIN, *Partial Unique Index*, dan *Native Range Partitioning* bulanan. |
| **In-Memory Cache & Queue** | Redis 7 Alpine (`requirepass`, AOF) | Buffer asinkron pencatatan audit log via `ProcessAuditLogJob` dan cache sub-milidetik untuk ringkasan statistik dashboard. |
| **Reverse Proxy & Web Server** | Nginx Alpine (Hardened) | Terminasi TLS 1.3, kompresi Brotli & Gzip level 6, mitigasi DDoS via `limit_req_zone`, pembatasan IP subnet untuk rute admin, dan *read-only filesystem* dengan `tmpfs`. |
| **Proteksi Perimeter** | VPN Gateway + `EnsureVpnAccess` Middleware | Pemisahan mutlak jalur akses admin dari internet publik dengan IP CIDR bitwise evaluation dan Stealth 404 blocking. |
| **Aset Frontend & Bundler** | Vite 6 + Vanilla JS / CSS (Carbon Flat 0px) | **100% Zero External CDN**. Font IBM Plex Sans dan seluruh dependensi dibundle lokal ke `public/build`. |
| **Connection Pooling** | PgBouncer | Mencegah lonjakan koneksi (*connection spikes*) dan kehabisan *database connection pool* pada beban ribuan user konkuren. |
| **Container Engine** | Docker Compose v3.8 | Penggunaan *Named Isolated Volumes* (`vendor_data`, `node_data`, `storage_data`) untuk menghilangkan latensi bind-mount di Cloud IDE (Antigravity) & Server. |

---

## 4. Struktur Model Data & Relasi Inti

Skema data dirancang untuk mendukung jutaan baris data secara efisien ([SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md)):

```
┌────────────────────────┐         ┌──────────────────────────────┐
│         skpd           │         │   master_jabatan_golongan    │
├────────────────────────┤         ├──────────────────────────────┤
│ id (BIGINT IDENTITY PK)│         │ id (BIGINT IDENTITY PK)      │
│ kode_skpd (VARCHAR UK) │         │ kategori ('JABATAN'/'GOLONG')│
│ nama_skpd (VARCHAR)    │         │ kode (VARCHAR UK)            │
│ is_active (BOOLEAN)    │         │ nama_label (VARCHAR)         │
└───────────┬────────────┘         └──────────────┬───────────────┘
            │                                     │
            └──────────────────┬──────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                        pendaftaran_tte                          │
├─────────────────────────────────────────────────────────────────┤
│ id (BIGINT IDENTITY PK)                                         │
│ nomor_referensi (VARCHAR 64 UK - Internal Superadmin Only)      │
│ nama_lengkap, no_hp                                             │
│ nik (TEXT AES-256-GCM), nip (TEXT AES-256-GCM)                  │
│ nik_hash (VARCHAR 64 - Partial UK), nip_hash (Partial UK)       │
│ skpd_id (FK), jabatan_id (FK), golongan_id (FK)                 │
│ status_memiliki_email (BOOLEAN)                                 │
│ email_existing (VARCHAR 255 NULL)                               │
│ email_usulan (VARCHAR 255 NULL - Auto Generated)                │
│ email_final_terverifikasi (VARCHAR 255 NULL - Definitif)        │
│ is_email_recap_confirmed (BOOLEAN DEFAULT FALSE)                │
│ status ('MENUNGGU_VERIFIKASI', 'DISETUJUI', 'DITOLAK')          │
│ catatan_penolakan (TEXT NULL)                                   │
│ verified_by (FK users), verified_at (TIMESTAMPTZ)               │
└──────────────────────────────┬──────────────────────────────────┘
                               │ (1:1)
                               ▼
┌──────────────────────────────┐
│        sertifikat_tte        │
├──────────────────────────────┤
│ id (BIGINT IDENTITY PK)      │
│ pendaftaran_id (FK UK)       │
│ email_dinas (VARCHAR 255 UK) │
│ tanggal_penerbitan (DATE)    │
│ tanggal_kadaluarsa (DATE)    │
│ status_sertifikat (VARCHAR)  │
│ verified_by_tahap2 (FK users)│
└──────────────────────────────┘

┌──────────────────────────────┐   ┌──────────────────────────────┐
│    dashboard_daily_stats     │   │  security_audit_logs (PART)  │
├──────────────────────────────┤   ├──────────────────────────────┤
│ id (BIGINT IDENTITY PK)      │   │ id, created_at (Composite PK)│
│ stat_date (DATE UK)          │   │ user_id, action_type         │
│ total_asn, total_terbit      │   │ ip_address, user_agent       │
│ total_antrian, total_warning │   │ payload_before (JSONB)       │
└──────────────────────────────┘   │ payload_after (JSONB)        │
                                   └──────────────────────────────┘
```

---

## 5. Alur Proses Bisnis Skala Nasional (End-to-End)

```
[ASN PUBLIK (Pelosok/Kota)]                [PANEL SUPERADMIN (VIA VPN GATEWAY)]
             │                                             │
    1. Isi Form Mandiri (/)                                │
       (Auto-save draft di localStorage)                   │
       (Local CAPTCHA fallback jika timeout)               │
       (Upload Berkas + SHA-256 Checksum)                  │
       (Generate usulan @cirebonkota.go.id)                │
             │                                             │
             ├──────── Submit Pendaftaran ────────────────>│
             │                                             │
             │                                    2. Akses Aman Melalui VPN (/gateway/login)
             │                                       - Verifikasi Subnet IP CIDR
             │                                       - Login Kredensial + TOTP MFA
             │                                             │
             │                                    3. Antrian Verifikasi Tahap 1
             │                                       - Review Identitas & Berkas
             │                                       - Review Usulan Email Dinas
             │                                             │
             │                                    4. Alur Penetapan Email Dinas:
             │                                       a) Klik "Gunakan Usulan"
             │                                          (Auto-copy usulan ke field)
             │                                       b) ATAU Edit Manual username
             │                                       c) Klik Tombol "Rekap Email"
             │                                          (Kunci email_final_terverifikasi)
             │                                             │
             │                                    5. Keputusan Tahap 1:
             │                                       - Setujui -> status: DISETUJUI
             │                                       - Tolak   -> status: DITOLAK (+ catatan)
             │                                             │
             │                                    [Proses Luar: Aktivasi Email & BSrE]
             │                                             │
             │                                    6. Verifikasi Tahap 2 (Sertifikat):
             │                                       - Input tanggal_penerbitan
             │                                       - Sistem hitung tanggal_kadaluarsa (+2th)
             │                                       - Buat sertifikat_tte (status: AKTIF)
             │                                             │
    (Penyampaian hasil via WhatsApp/              7. Scheduler Harian & Redis Cache:
     kontak resmi di luar sistem)                    - Scan masa berlaku (H-3 Warning)
                                                     - Update dashboard_daily_stats
                                                     - Sub-ms Dashboard Analytics
                                                     - Streaming Mass Export (RAM < 20MB)
```

---

## 6. Modul & Fitur Aplikasi

### 6.1 Modul Pendaftaran Publik & Resiliensi Pelosok
- **Offline-First Draft Auto-Save (Aman Tanpa PII NIK/NIP):** Skrip JavaScript menyimpan input form ke `sessionStorage` (`tte_registration_draft_temp`) setiap ketikan pengguna. Untuk memenuhi UU PDP No. 27/2022, data sensitif NIK 16 digit dan NIP 18 digit **TIDAK PERNAH disimpan di browser klien**. Saat konektivitas seluler terputus, formulir menampilkan status *"Draft aman tersimpan di sesi browser"*. Saat formulir disubmit, draft otomatis dibersihkan ([DESIGN.md §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#7-pola-state-ui-loading--success--empty--error)).
- **Fast-Timeout CAPTCHA & Local Fallback:** Pemanggilan Google reCAPTCHA v2/v3 diproteksi batas waktu `Http::timeout(3)`. Jika jaringan eksternal gagal atau lambat, sistem beralih otomatis menyajikan tantangan *Local Math-Based CAPTCHA* berpenanda tangan HMAC agar ASN tidak terhalang mendaftar.
- **Generator Email Usulan:** Jika ASN memilih `status_memiliki_email = false`, sistem menjalankan `EmailGeneratorService` untuk membuat usulan format baku `nama.nip@cirebonkota.go.id` yang membersihkan seluruh gelar akademik/keagamaan.
- **Formulir Ringan Tanpa Unggah Berkas:** Seluruh data pendaftaran berupa teks murni (payload < 5 KB) untuk menjamin transmisi instan di wilayah jaringan minim.

### 6.2 Modul Verifikasi Tahap 1 & Alur Rekap Email Dinas
- **Policy Enforcement:** Seluruh method aksi controller diproteksi secara ketat oleh `PendaftaranTtePolicy`.
- **Antrian Verifikasi FIFO:** Menampilkan daftar pendaftar berstatus `MENUNGGU_VERIFIKASI` dengan Eager Loading relasi `skpd`, `jabatan`, dan `golongan`.
- **Interaksi Penetapan Email Dinas oleh Superadmin:**
  1. Superadmin memeriksa `email_usulan` yang digenerate sistem atau `email_existing`.
  2. Disediakan tombol **"Gunakan Usulan"** yang secara instan menyalin teks usulan ke field input email dinas definitif.
  3. Superadmin diberikan keleluasaan penuh untuk **mengedit/menyesuaikan username email secara manual** pada field input `@cirebonkota.go.id` bila ditemukan karakter khusus, singkatan nama instansi, atau preferensi resmi.
  4. Di sebelah kanan field input, terdapat tombol aksi **"Rekap Email"**. Saat diklik, sistem menyimpan `email_final_terverifikasi` dan menandai flag `is_email_recap_confirmed = true`.
- **Keputusan Verifikasi:** Superadmin menyetujui (status `DISETUJUI`) atau menolak (status `DITOLAK` wajib disertai `catatan_penolakan`).

### 6.3 Modul Verifikasi Tahap 2 & Penerbitan Sertifikat
- **Policy Enforcement:** Seluruh method aksi controller diproteksi secara ketat oleh `SertifikatTtePolicy`.
- **Pencatatan Penerbitan Sertifikat:** Superadmin menginput `tanggal_penerbitan` setelah akun email dinas dikonfirmasi aktif di server mail.
- **Kalkulasi Otomatis Masa Berlaku:** Sistem menetapkan `tanggal_kadaluarsa` secara presisi (`tanggal_penerbitan + 2 tahun`) dan menginisialisasi status sertifikat `AKTIF`.
- **Segregation of Duty Logging:** Mencatat identitas Superadmin Tahap 2 di kolom `verified_by_tahap2` dan memicu evaluasi alert audit jika diproses oleh personel yang sama dengan Tahap 1 ([SECURITY.md §6.3](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#63-segregation-of-duty-mitigasi-pada-model-superadmin)).

### 6.4 Modul Dashboard Superadmin & Fast Aggregator
- **Sub-Millisecond Dashboard Statistics:** Mengeliminasi eksekusi query berat `COUNT(*)` pada jutaan baris data pendaftaran. Data indikator statistik diambil dari tabel fast counter `dashboard_daily_stats` melalui layer Redis Cache:
```php
$stats = Cache::remember('dashboard_stats_summary', 300, function () {
    return DB::table('dashboard_daily_stats')
        ->where('stat_date', today())
        ->first();
});
```
- **Indikator Kunci Dashboard:**
  1. *Total ASN Terdaftar*
  2. *Sertifikat Aktif Terbit*
  3. *Antrian Verifikasi Tahap 1*
  4. *Warning H-3 Kedaluwarsa* (Aksen visual kartu warning berprioritas tertinggi).
- **Pemeringkatan Top 10 SKPD Berbasis Adopsi (%):**
  Menggunakan rasio `(COUNT(pendaftaran_tte.id) / skpd.total_asn) * 100%` untuk mengeliminasi bias ukuran (*size bias*), dilengkapi tombol toggle mode (Persentase vs Volume) dan tombol aksi cepat menuju halaman analitik penuh.

### 6.6 Modul Analitik Capaian Adopsi TTE Seluruh SKPD (`CapaianSkpdController`)
Untuk memberikan visibilitas komprehensif tanpa batasan 10 besar, modul ini menyediakan:
- **Agregasi Metrik Kota:** Menghitung total pendaftar, total pegawai, total sertifikat terbit, dan rata-rata capaian kota dalam single optimized query.
- **Tabel Paginasi Seluruh SKPD:** Menampilkan setiap SKPD aktif dengan kolom peringkat, total pegawai, pendaftar, terbit, persentase adopsi, progress bar visual 4 warna, dan badge status.
- **Filter & Sorting Dinamis:** Mendukung parameter query `q` (pencarian nama/singkatan), `kategori` (sangat_tinggi >=75%, tinggi 50-74%, sedang 25-49%, rendah <25%), serta pengurutan dinamis berdasarkan persentase atau kolom lainnya dengan retensi parameter paginasi.

### 6.5 Modul Manajemen Data Master & TOTP MFA
- CRUD unit kerja SKPD dengan kode SKPD unik dan status aktif.
- CRUD master kategori Jabatan dan Golongan (`master_jabatan_golongan`) untuk standardisasi data ASN.
- Manajemen akun internal Superadmin, aktivasi TOTP MFA dengan persistensi recovery codes terenkripsi, dan proteksi step-up reauthentication (`current_password`) saat menonaktifkan MFA.

---

## 7. Modul Pelaporan & Streaming Export

Untuk mendukung pelaporan berkala tanpa risiko kegagalan memori (*Out of Memory / 500 Error*) dan bebas dari badai kueri N+1 (*zero N+1 query storm*), modul ekspor menerapkan pola **Database Cursor Streaming Join Terpadu** dengan konfigurasi unbuffered FastCGI:

```php
namespace App\Http\Controllers;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLaporanController extends Controller
{
    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportLaporan', PendaftaranTte::class);

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, ['No Referensi', 'Nama Lengkap', 'SKPD', 'Jabatan', 'Golongan', 'Email Dinas', 'Status']);

            // Database Cursor: Memori PHP stabil < 20MB & 0 N+1 Queries untuk 1.000.000+ records
            DB::table('pendaftaran_tte')
                ->leftJoin('skpd', 'pendaftaran_tte.skpd_id', '=', 'skpd.id')
                ->leftJoin('master_jabatan_golongan as jab', 'pendaftaran_tte.jabatan_id', '=', 'jab.id')
                ->leftJoin('master_jabatan_golongan as gol', 'pendaftaran_tte.golongan_id', '=', 'gol.id')
                ->select([
                    'pendaftaran_tte.nomor_referensi',
                    'pendaftaran_tte.nama_lengkap',
                    'skpd.nama_skpd',
                    'jab.nama_label as nama_jabatan',
                    'gol.nama_label as nama_golongan',
                    'pendaftaran_tte.email_final_terverifikasi',
                    'pendaftaran_tte.email_existing',
                    'pendaftaran_tte.status'
                ])
                ->orderBy('pendaftaran_tte.id', 'asc')
                ->cursor()
                ->each(function ($row) use ($handle) {
                    fputcsv($handle, [
                        $row->nomor_referensi,
                        $row->nama_lengkap,
                        $row->nama_skpd ?? '-',
                        $row->nama_jabatan ?? '-',
                        $row->nama_golongan ?? '-',
                        $row->email_final_terverifikasi ?? $row->email_existing ?? '-',
                        $row->status
                    ]);
                });

            fclose($handle);
        }, 'laporan_monitoring_tte_nasional.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
```

---

## 8. Struktur Teknis (Clean Domain Architecture — Laravel 13)

Kode aplikasi diorganisasikan per domain fungsional untuk memudahkan skalabilitas dan pemeliharaan:

```
app/
├── Domain/
│   ├── Pendaftaran/
│   │   ├── Models/
│   │   │   ├── PendaftaranTte.php             // Cast AES-256 & HMAC Blind Index
│   │   │   └── PendaftaranLampiran.php        // Metadata Berkas & SHA-256 Checksum
│   │   ├── Actions/
│   │   │   ├── StorePendaftaranAction.php
│   │   │   ├── ApproveTahap1Action.php
│   │   │   ├── RejectPendaftaranAction.php
│   │   │   └── RekapEmailDinasAction.php       // Konfirmasi Email Definitif Superadmin
│   │   └── Observers/
│   │       └── AuditObserver.php              // Dispatch Job ke Redis Queue
│   ├── Sertifikat/
│   │   ├── Models/
│   │   │   └── SertifikatTte.php
│   │   └── Actions/
│   │       ├── TerbitkanSertifikatAction.php
│   │       └── UpdateStatusSertifikatAction.php
│   ├── Master/
│   │   ├── Models/
│   │   │   ├── Skpd.php
│   │   │   └── MasterJabatanGolongan.php
│   │   └── Actions/
│   └── Analytics/
│       ├── Models/
│       │   └── DashboardDailyStats.php
│       └── Actions/
│           └── RefreshDailyAggregatorAction.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php             // Auth Superadmin via VPN Gateway
│   │   ├── PendaftaranController.php          // Form Publik (Submit only)
│   │   ├── VerifikasiController.php           // Antrian, Rekap Email, Approve/Reject
│   │   ├── PenerbitanController.php           // Tahap 2 Sertifikat TTE
│   │   ├── DashboardController.php            // Analytics Fast Counter
│   │   └── ExportLaporanController.php        // Cursor Streaming Mass Export
│   ├── Requests/
│   │   ├── StorePendaftaranRequest.php        // Validasi NIK/NIP, reCAPTCHA/Local CAPTCHA
│   │   └── RekapEmailDinasRequest.php         // Validasi format @cirebonkota.go.id
│   └── Middleware/
│       ├── EnsureVpnAccess.php                // Proteksi Perimeter Subnet IP CIDR VPN
│       ├── SecurityHeaders.php                // HSTS, CSP, X-Frame-Options
│       ├── SanitizeInput.php                  // XSS Input Sanitization
│       └── ThrottlingMiddleware.php           // Multi-layer Rate Limiter
├── Jobs/
│   ├── ProcessAuditLogJob.php                 // Worker Queue: 'audit-logs'
│   ├── UpdateSertifikatStatusJob.php          // Daily Cron Scanner (H-3/Expired)
│   └── RefreshDailyAggregatorJob.php          // Hourly Fast Aggregator Builder
├── Services/
│   ├── EmailGeneratorService.php              // Auto-generate @cirebonkota.go.id
│   ├── LocalCaptchaService.php                // Local Math CAPTCHA Engine & Fallback
│   └── TotpService.php                        // RFC 6238 TOTP MFA Engine
└── Policies/
    └── PendaftaranTtePolicy.php               // RBAC Gate Superadmin
config/
└── vpn.php                                    // Konfigurasi Subnet & Secret VPN Gateway
```

---

## 9. Infrastruktur & Deployment (Docker Compose Enterprise)

### 9.1 Konfigurasi `docker-compose.yml` Standar Produksi

```yaml
version: '3.8'

services:
  # 1. NGINX REVERSE PROXY (EDGE)
  nginx:
    build:
      context: ./docker/nginx
      dockerfile: Dockerfile
    image: tte_nginx:production
    container_name: tte_nginx
    restart: always
    ports:
      - "${HTTP_PORT:-80}:80"
      - "${APP_PORT:-443}:443"
    volumes:
      - .:/var/www/html:ro                       # Codebase Read-Only
      - storage_data:/var/www/html/storage       # Storage Volume Writable
    networks:
      - frontend
    security_opt:
      - "no-new-privileges:true"
    read_only: true
    tmpfs:
      - /var/cache/nginx:exec,mode=0755
      - /var/run:exec,mode=0755
      - /tmp:mode=1777
    cpus: 0.50
    mem_limit: 256m
    depends_on:
      - app

  # 2. LARAVEL 13 RUNTIME & QUEUE WORKER
  app:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    image: tte_php:8.3-fpm
    container_name: tte_app
    restart: always
    volumes:
      - .:/var/www/html
      - vendor_data:/var/www/html/vendor         # Named Volume: Bebas Latensi I/O
      - node_data:/var/www/html/node_modules     # Named Volume: Bebas Latensi I/O
      - storage_data:/var/www/html/storage
    networks:
      - frontend
      - backend
    security_opt:
      - "no-new-privileges:true"
    cpus: 1.00
    mem_limit: 512m
    env_file:
      - .env
    depends_on:
      - postgres
      - redis

  # 3. POSTGRESQL 16 ENTERPRISE ENGINE
  postgres:
    image: postgres:16-alpine
    container_name: tte_postgres
    restart: always
    environment:
      POSTGRES_DB: ${DB_DATABASE:-tte_monitoring}
      POSTGRES_USER: ${DB_USERNAME:-postgres}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - pg_data:/var/lib/postgresql/data
    networks:
      - backend
    security_opt:
      - "no-new-privileges:true"
    cpus: 1.50
    mem_limit: 1024m

  # 4. REDIS 7 BUFFER & EVENT QUEUE
  redis:
    image: redis:7-alpine
    container_name: tte_redis
    restart: always
    command: redis-server --requirepass ${REDIS_PASSWORD} --appendonly yes
    volumes:
      - redis_data:/data
    networks:
      - backend
    security_opt:
      - "no-new-privileges:true"
    cpus: 0.50
    mem_limit: 256m

networks:
  frontend:
    driver: bridge
  backend:
    driver: bridge
    internal: true                                # Isolasi Penuh: Database & Redis Tersembunyi

volumes:
  pg_data:
  redis_data:
  vendor_data:
  node_data:
  storage_data:
```

---

## 10. Scheduler & Background Jobs

Sistem mengeksekusi proses asinkron melalui kombinasi **Redis Queue Worker** dan **Laravel Task Scheduler**:

1. **Redis Queue Worker (`php artisan queue:work --queue=audit-logs`):**
   - Mengambil event logging dari `AuditObserver` dan menyimpannya secara *non-blocking* ke tabel `security_audit_logs`.
2. **Daily Sertifikat Lifecycle Job (`UpdateSertifikatStatusJob`):**
   - Berjalan harian (pukul 00:01 WIB) memindai `sertifikat_tte.tanggal_kadaluarsa`.
   - Mengubah status menjadi `WARNING_H3` saat `H-3`, dan `EXPIRED` saat melewati tanggal kadaluarsa.
3. **Daily Stats Refresh Job (`RefreshDailyAggregatorJob`):**
   - Menghitung agregasi harian ke `dashboard_daily_stats` dan merefresh kunci cache Redis `dashboard_stats_summary`.

---

## 11. Kontrol Dokumen

| Item | Keterangan |
| :--- | :--- |
| **Versi Dokumen** | **3.1.0-PROD** |
| **Dokumen Acuan** | [PRD.md](file:///f:/sismon-tte-testing-V4/docs/PRD.md) v3.1.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.1.0-PROD, [SECURITY.md](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md) v3.1.0-PROD |
| **Riwayat Perubahan** | • v1.3.0: Rekonstruksi arsitektur 2-peran.<br>• v2.1.0-PROD: Standardisasi Enterprise Skala Nasional: Stack Laravel 13, PostgreSQL 16 Enterprise DDL, Isolated Named Docker Volumes, Segmentasi Jaringan `internal: true`, Asynchronous Audit Logging via Redis 7 Queue, Fast Aggregator `dashboard_daily_stats`, Alur Interaktif Rekap & Edit Email Superadmin (`@cirebonkota.go.id`), dan Arsitektur Resiliensi Low-Bandwidth.<br>• **v3.1.0-PROD:** Penambahan arsitektur **Isolasi Jalur Admin Berbasis VPN Gateway (Zero-Trust Network Perimeter, `EnsureVpnAccess` Middleware, dan Stealth 404 Defense)**. |
| **Target Pembaca** | System Architect, DevOps Engineer, Backend & Frontend Developers, Tim Keamanan Siber |
### 7.2 Ekspor Data Capaian Adopsi SKPD Format CSV (`admin.capaian-skpd.export`)
Modul ekspor capaian adopsi pada `CapaianSkpdController::export()` mengadopsi pola *memory-safe streaming* via `response()->streamDownload()`:
- **UTF-8 Byte Order Mark (`\xEF\xBB\xBF`):** Memastikan file CSV langsung terbaca rapi pada spreadsheet (Microsoft Excel) tanpa masalah encoding karakter.
- **Filter-Aware Streaming:** Menjaga konsistensi data yang diunduh dengan filter pencarian dan kategori yang sedang aktif pada layar admin.
- **Nama Berkas Otomatis:** Menggunakan format penamaan bertanda waktu presisi: `capaian_adopsi_tte_skpd_YYYYMMDD_HHmmss.csv`.
