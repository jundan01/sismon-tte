# PRD.md — Product Requirements Document

**Produk:** Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas  
**Instansi:** Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon (DKIS Kota Cirebon) / Bidang Persandian  
**Domain Resmi Email Dinas:** `@cirebonkota.go.id`  
**Target Skalabilitas:** 100.000 – 1.000.000+ Records (Enterprise Skala Nasional)  
**Target Lingkungan:** Ramah Daerah Terpencil (*Low Connectivity / High Latency* 2G/3G/Edge) & Cloud IDE (Antigravity)  
**Core Stack:** PHP 8.3 / Laravel 13 + PostgreSQL 16 + Redis 7 + Nginx + Docker Compose  
**Dokumen Terkait:** [ARCHITECTURE.md](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md) v3.2.0-PROD, [DESIGN.md](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md) v3.2.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.2.0-PROD, [SECURITY.md](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md) v3.2.0-PROD, [OPERATIONAL_GUIDE.md](file:///f:/sismon-tte-testing-V4/docs/OPERATIONAL_GUIDE.md) v3.2.0-PROD, [solusi_audit.md](file:///f:/sismon-tte-testing-V4/solusi_audit.md)  
**Versi Dokumen:** 3.2.0-PROD  
**Status:** Ditetapkan sebagai Spesifikasi Produk Final Skala Nasional (Laravel 13 + Zero-Trust VPN Gateway + Post-Audit Hardened)  

---

> **Catatan Pembaruan Rilis v3.1.0-PROD (Catatan Sebelumnya):**
> 1. **Peningkatan Skalabilitas & Stack Framework:** Mengadopsi **Laravel 13** dan **PostgreSQL 16 Enterprise** dengan kapasitas olah data 100.000 hingga 1.000.000+ records ASN.
> 2. **Isolasi Jalur Masuk Admin Berbasis VPN Gateway (Zero-Trust Network Segmentation):**
>    - Menghapus rute login admin dari rute publik terbuka (`/login`).
>    - Memindahkan seluruh pintu masuk autentikasi dan panel kendali Superadmin ke jalur terproteksi (`/gateway/login` dan `/admin/*`) yang hanya dapat diakses melalui **VPN Gateway / Intranet DKIS Kota Cirebon** dengan verifikasi IP Subnet CIDR (`EnsureVpnAccess`).
>    - Seluruh permintaan dari internet publik ke rute admin akan menghasilkan respon *Stealth Mode* (HTTP 404 Not Found) untuk mengeliminasi surface reconnaissance dan brute-force.
> 3. **Standardisasi Domain Email:** Mempertahankan dan menegakkan domain resmi **`@cirebonkota.go.id`** untuk seluruh ASN pendaftar.
> 4. **Alur Fleksibel Penetapan Email Dinas oleh Superadmin:**
>    - Jika pendaftar belum memiliki email (`status_memiliki_email = false`), sistem secara otomatis meng-generate `email_usulan`.
>    - Pada modul verifikasi data Superadmin, disediakan kontrol interaktif: Superadmin dapat **menerima usulan email** (otomatis tercopy ke field input email `@cirebonkota.go.id`) ATAU **mengedit username email secara manual** di field input tersebut.
>    - Di sisi kanan field input email, disediakan tombol aksi **"Rekap Email"** untuk mengonfirmasi dan mengunci penetapan email dinas definitif (`email_final_terverifikasi`) sebelum sertifikat diterbitkan.
> 5. **Resiliensi Konektivitas Daerah Terpencil (Low-Bandwidth Resilience):**
>    - Mandat **100% Zero External CDN** (semua aset JS, CSS, dan font IBM Plex Sans dibundle lokal via Vite).
>    - Fitur **Client-Side Draft Auto-Save (`localStorage`)** dan *auto-retry submission* saat jaringan seluler terputus.
>    - **Fast Timeout & Local Math CAPTCHA Fallback:** Google reCAPTCHA dibatasi timeout 3 detik, dengan fallback otomatis ke tantangan matematika lokal jika jaringan internet luar terganggu.
> 6. **Formulir Mandiri Ramping & Cepat (Lean Registration Flow):** Alur pendaftaran difokuskan pada pengumpulan data kepegawaian ASN dan standardisasi akun email dinas tanpa hambatan unggah berkas fisik/lampiran dokumen.
> 7. **High-Performance Background Logging & Fast Dashboard:** Asynchronous Audit Logging via **Redis 7 Queue** dan agregasi harian statistik melalui tabel `dashboard_daily_stats` dengan Redis Cache sub-milidetik.
> 8. **Memory-Safe Mass Export:** Pengunduhan laporan ratusan ribu data menggunakan Database Cursor Streaming (`streamDownload`) dengan pemakaian RAM PHP stabil < 20MB.
> 
> ---
> 
> **Catatan Pembaruan Rilis v3.2.0-PROD (Remediasi Audit Kode Program 4 Pilar):**
> 1. **Kepatuhan Privasi Data ASN di Browser (UU PDP):** Pengalihan draft form ke `sessionStorage` (`tte_registration_draft_temp`) dengan penjaminan zero NIK/NIP tersimpan di browser klien serta pembersihan otomatis saat form disubmit.
> 2. **Otorisasi & Keamanan MFA Superadmin:** Penegakan *step-up reauthentication* (`current_password`) saat menonaktifkan MFA, penyimpanan 8 backup recovery codes terenkripsi ke database (`users.mfa_recovery_codes`), dan pencatatan audit log `DISABLE_MFA_USER`.
> 3. **Penegakan Eksplisit Kebijakan Otorisasi:** Pendaftaran policy binding pada `AppServiceProvider` dan penerapan `$this->authorize(...)` di seluruh controller operasional (`VerifikasiController`, `PenerbitanController`, `ExportLaporanController`).
> 4. **Optimasi Mass Streaming Export Bebas N+1:** Menggunakan `DB::table()->leftJoin()->cursor()` dalam 1 kueri terpadu serta konfigurasi unbuffered proxy Nginx (`fastcgi_buffering off; fastcgi_read_timeout 300;`).
> 5. **Ketahanan Partisi Log Audit:** Penambahan partisi fallback `security_audit_logs_default` pada PostgreSQL 16.
> 6. **Sanitasi Kode & Strict Environment:** Pembersihan duplikasi validasi form, perbaikan regex email preview, refaktor DRY exception handler, optimasi nomor referensi, dan kewajiban deklarasi password produksi pada Docker Compose.

---

## 1. Ringkasan Eksekutif

Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas adalah platform enterprise terpadu yang melayani pendaftaran mandiri sertifikat Tanda Tangan Elektronik (TTE) bagi Aparatur Sipil Negara (ASN), standardisasi penerbitan akun email dinas resmi `@cirebonkota.go.id` sebagai prasyarat Balai Sertifikasi Elektronik (BSrE), serta pemantauan proaktif masa berlaku sertifikat TTE skala nasional.

Sistem dirancang tangguh beroperasi pada dua spektrum lingkungan:
1. **Backend Skala Tinggi (High-Concurrency) & Zero-Trust Security:** Mampu mengelola antrian transaksi jutaan data ASN, enkripsi sensitif AES-256-GCM, isolasi jalur masuk admin via VPN Gateway, dan logging asinkron tanpa *table locking* atau *memory leak*.
2. **Frontend Ramah Wilayah Terpencil (Low-Bandwidth Resilience):** Memastikan ASN di area dengan keterbatasan sinyal (2G/3G/Edge) dapat mengakses halaman pendaftaran dalam < 2 detik tanpa kegagalan form atau kehilangan draf isian data.

---

## 2. Masalah & Tantangan Skala Nasional

| Masalah Konvensional | Tantangan Skala Nasional | Solusi Sistem v3.1.0-PROD |
| :--- | :--- | :--- |
| **Ketergantungan Google Form & Rekap Manual** | Rentan salah input data, lambat diproses pada volume puluhan/ratusan ribu ASN | Form mandiri terstruktur dengan validasi ketat, normalisasi master jabatan/golongan, dan upload lampiran berkas |
| **Administrative Surface Exposure di Rute Publik** | Endpoint login admin di rute publik terbuka menjadi sasaran scanning dan brute force | Pemisahan mutlak jalur admin dengan **VPN Gateway (Stealth 404 pada akses publik)** |
| **Ketidakkonsistenan Format Email Dinas** | Usulan manual sering duplikat atau melanggar aturan format `@cirebonkota.go.id` | Generator otomatis `email_usulan` + Panel Verifikasi Superadmin interaktif (tombol terima usulan, editable manual, dan tombol rekap email pasti) |
| **Kegagalan Akses Jaringan di Daerah Pelosok** | Halaman *blank/hanging* akibat ketergantungan CDN eksternal atau koneksi seluler tidak stabil | Arsitektur 100% Zero External CDN, kompresi Nginx Brotli/Gzip level 6, Client-side Draft Auto-save (`localStorage`), dan Fast-timeout Local Math CAPTCHA Fallback |
| **Lonjakan Beban & Kebocoran Memori (OOM)** | Query `COUNT(*)` dan export Excel pada jutaan data membuat server database terkunci dan PHP crash | Fast Counter `dashboard_daily_stats` + Redis Caching & Cursor Streaming Mass Export (`streamDownload` RAM < 20MB) |
| **Sertifikat TTE Kedaluwarsa Tanpa Peringatan** | Risiko terganggunya penandatanganan dokumen kedinasan penting | Task Scheduler harian otomatis memindai masa berlaku (H-3 Alert, Overdue, Expired, Revoked) |
| **Risiko Kebocoran Data Pribadi (UU PDP)** | Ancaman pembobolan data NIK, NIP, dan identitas ASN | Enkripsi *at-rest* AES-256-GCM + Blind Indexing HMAC-SHA256, RBAC Superadmin + TOTP MFA, VPN Gateway, dan Native Partitioned Audit Logs |

---

## 3. Tujuan Produk

### 3.1 Tujuan Bisnis & Operasional
1. Menyediakan gerbang pendaftaran mandiri TTE yang ramah pengguna, cepat, dan anti-gagal di seluruh penjuru wilayah instansi.
2. Mengisolasi penuh jalur autentikasi dan administrasi Superadmin melalui VPN Gateway agar tidak dapat diakses atau dipindai oleh publik.
3. Menstandarkan dan mempercepat proses penerbitan usulan email dinas resmi `@cirebonkota.go.id`.
4. Memberikan fleksibilitas penuh bagi Superadmin dalam memverifikasi, mengedit, dan merekap email definitif ASN sebelum penerbitan sertifikat.
5. Menyediakan sistem pemantauan dini (H-3 Warning) siklus hidup sertifikat TTE secara otomatis dan akurat.
6. Menyediakan laporan analitik dan rekapitulasi data pendaftaran berkinerja tinggi yang siap diekspor untuk kebutuhan pimpinan.

### 3.2 Tujuan Teknis & Arsitektur
- **Infrastruktur Bebas Lag:** Pemanfaatan *Docker Named Isolated Volumes* (`vendor_data`, `node_data`, `storage_data`) dan segmentasi jaringan terisolasi (`backend: internal: true`) sesuai [ARCHITECTURE.md §9](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#9-infrastruktur--deployment).
- **Keamanan Zero-Trust & VPN Gateway:** Perlindungan data sensitif, isolasi jalur admin via middleware `EnsureVpnAccess`, audit trail asinkron, dan hardening menyeluruh sesuai [SECURITY.md](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md).
- **Integritas Skema Data:** Struktur tabel ternormalisasi, partisi bulanan audit logs, dan partial unique indexing sesuai [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md).
- **Pengalaman Visual Berstandar Tinggi:** UI bertema Carbon Flat-Square (0px radius) dengan penanganan state konektivitas, badge status VPN, dan interaksi rekap email sesuai [DESIGN.md](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md).

---

## 4. Target Pengguna & Persona

| Persona | Peran & Akses | Tanggung Jawab & Alur Kerja | Titik Sakit yang Diselesaikan |
| :--- | :--- | :--- | :--- |
| **ASN Pendaftar** | **Publik** (Tanpa login, form mandiri satu halaman di `/`) | Mengisi data identitas (NIK, NIP, Nama, SKPD, Jabatan, Golongan, No HP), menentukan status kepemilikan email dinas, mengunggah berkas lampiran, dan mengirimkan pendaftaran | Bebas dari form yang lambat/rusak saat sinyal buruk; tidak kehilangan isian data berkat draft auto-save; proses mudah tanpa perlu registrasi akun; tidak terekspos ke menu internal |
| **Superadmin** | **Internal Terproteksi VPN** (Wajib Jalur VPN Gateway + Kredensial + TOTP MFA di `/gateway/login`) | 1. Memverifikasi kelayakan data identitas ASN (Tahap 1)<br>2. Menerima usulan email, mengedit manual, dan menekan tombol **"Rekap Email"** definitif<br>3. Menginput tanggal terbit sertifikat (Tahap 2)<br>4. Memantau dashboard & sertifikat H-3 Expired<br>5. Mengelola master SKPD, Jabatan, Golongan, dan Akun<br>6. Menjalankan streaming mass export laporan | Akses terlindungi penuh dari ancaman serangan siber publik via VPN Gateway; tidak perlu rekap manual di spreadsheet; sistem otomatis menyajikan usulan email dan tombol rekap cepat; monitoring H-3 otomatis; export data massal tidak membuat server crash |

---

## 5. Ruang Lingkup Sistem

### 5.1 Dalam Lingkup (In-Scope)
- **Form Pendaftaran Publik Resilien:** Formulir pendaftaran mandiri dengan proteksi Google reCAPTCHA (timeout 3 detik) + Fallback Local Math CAPTCHA, integrasi draft auto-save `localStorage`, dan upload berkas lampiran ber-checksum SHA-256 pada rute publik `/`.
- **Isolasi Rute Admin Berbasis VPN Gateway:**
  - Penutupan rute `/login` publik terbuka.
  - Penempatan rute auth admin di `/gateway/login` dan panel admin di `/admin/*` yang di-guard oleh middleware `EnsureVpnAccess` (validasi IP CIDR dan header secret).
  - Respon stealth HTTP 404 (Not Found) untuk segala request dari IP publik non-VPN.
- **Validasi Keunikan NIK/NIP Pintar:** Validasi format (16 digit NIK, 18 digit NIP) dengan *Partial Unique Index* (mengizinkan pendaftaran ulang baris baru jika status sebelumnya `DITOLAK`).
- **Mesin Generator & Rekap Email Dinas:**
  - Auto-generate usulan email berbasis nama dan NIP pendaftar (`@cirebonkota.go.id`).
  - Panel Superadmin untuk review usulan email, fitur *one-click copy* usulan ke field input email, kemampuan edit manual username, dan tombol aksi "Rekap Email" pasti.
- **Alur Verifikasi Dua Tahap:**
  - *Tahap 1 (Data & Email):* Validasi identitas, kelengkapan berkas, penetapan/rekap email dinas, dan keputusan (Setujui / Tolak + alasan penolakan).
  - *Tahap 2 (Sertifikat TTE):* Input tanggal terbit setelah email aktif di server mail, sistem otomatis menghitung `tanggal_kadaluarsa` (terbit + 2 tahun), dan status sertifikat diset `AKTIF`.
- **Monitoring Siklus Hidup Sertifikat:** Scheduler harian otomatis memindai masa berlaku (`AKTIF`, `WARNING_H3`, `EXPIRED`, `REVOKED`) dan menyuplai fast counter Dashboard.
- **Pelaporan & Streaming Export:** Rekapitulasi data pendaftaran dan sertifikat dengan filter tanggal/SKPD dalam format Excel dan CSV via Cursor Streaming.
- **Manajemen Data Master:** CRUD unit kerja SKPD, master data Jabatan & Golongan, serta manajemen akun Superadmin.
- **Keamanan Enterprise & Observability:** Enkripsi AES-256-GCM, VPN Gateway IP Inspection, TOTP MFA Superadmin, Rate Limiting 2-lapis, Asynchronous Audit Logging via Redis Queue, dan partisi tabel PostgreSQL bulanan.

### 5.2 Di Luar Lingkup (Out-of-Scope)
- **Provisioning Akun Mail Server Riil:** Sistem mengelola standardisasi, usulan, dan rekapitulasi data email definitif; sinkronisasi pembuatan mailbox fisik di server Zimbra/cPanel/Mail Server instansi tetap berjalan melalui SOP internal di luar sistem.
- **Integrasi API Langsung ke Server BSrE:** Tanggal terbit sertifikat diinput manual oleh Superadmin berdasarkan notifikasi resmi dari BSrE.
- **Status Tracking Publik:** ASN pendaftar bertindak murni sebagai submitter; hasil verifikasi/penolakan disampaikan oleh petugas melalui kanal resmi (WhatsApp/kontak langsung). `nomor_referensi` berstatus referensi internal Superadmin.
- **Aplikasi Mobile Native Khusus:** Antarmuka publik dan panel admin berbasis Web Responsive berkemampuan tinggi.

---

## 6. Alur Pengguna Utama (User Stories)

### 6.1 ASN Pendaftar (Publik)
- *Sebagai ASN di wilayah kerja/pelosok,* saya ingin mengisi form pendaftaran TTE secara cepat di browser HP saya, agar data saya terkirim tanpa terhambat koneksi internet yang lambat.
- *Sebagai ASN yang koneksi internetnya terputus saat mengisi form,* saya ingin isian form saya tersimpan otomatis di perangkat saya, sehingga saya tidak perlu mengetik ulang saat koneksi kembali tersambung.
- *Sebagai ASN yang belum memiliki email dinas,* saya ingin sistem mencatat kebutuhan saya dan mengusulkan pembuatan email dinas resmi `@cirebonkota.go.id`.
- *Sebagai ASN yang pernah ditolak karena salah berkas,* saya ingin dapat mendaftar ulang dengan NIK/NIP yang sama setelah menerima informasi dari petugas, tanpa akun saya terkunci selamanya.

### 6.2 Superadmin (Melalui VPN Gateway)
- *Sebagai Superadmin yang terhubung ke VPN Diskominfo,* saya ingin mengakses portal login admin melalui `/gateway/login` secara aman tanpa kekhawatiran adanya bot publik yang mengganggu sistem.
- *Sebagai Superadmin,* saya ingin melihat antrian pendaftaran lengkap dengan usulan email dinas yang digenerate sistem, sehingga saya dapat memverifikasi kesesuaian nama dan NIP secara cepat.
- *Sebagai Superadmin,* saya ingin dapat mengklik tombol untuk menerima usulan email atau mengedit nama email secara manual pada field `@cirebonkota.go.id`, lalu menekan tombol "Rekap Email" untuk menetapkan data email definitif yang sah.
- *Sebagai Superadmin,* saya ingin menyetujui atau menolak pendaftaran dengan alasan yang tercatat dan terenkripsi, agar akuntabilitas birokrasi terjaga.
- *Sebagai Superadmin,* saya ingin menginput tanggal penerbitan sertifikat dan membiarkan sistem menghitung tanggal kedaluwarsa secara otomatis.
- *Sebagai Superadmin,* saya ingin melihat statistik dashboard seketika (< 50ms) dan menerima peringatan otomatis untuk sertifikat yang memasuki masa H-3 kedaluwarsa.
- *Sebagai Superadmin,* saya ingin mengunduh laporan rekapitulasi ratusan ribu data dalam format CSV/Excel secara langsung tanpa khawatir server mengalami *memory leak* atau *down*.

---

## 7. Kebutuhan Fungsional (Functional Requirements)

Prioritas: **Must (Wajib)** | **Should (Penting)** | **Could (Tambahan)**

### 7.1 Modul Pendaftaran Publik & Resiliensi Pelosok
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-01** | Akses form pendaftaran publik tanpa login pada route `/`, dioptimalkan untuk perangkat mobile dan low-bandwidth | [DESIGN.md §5.1](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#51-portal-publik--single-column-resilien-mobile-first) | Must |
| **FR-01a** | Proteksi submit dengan Google reCAPTCHA v2/v3 dengan batas timeout maksimal 3 detik | [SECURITY.md §5.2](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#52-fast-timeout-recaptcha--local-math-captcha-engine) | Must |
| **FR-01b** | Fallback otomatis ke **Local Math-Based CAPTCHA** jika Google reCAPTCHA tidak terjangkau / timeout | [ARCHITECTURE.md §6.1](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#61-modul-pendaftaran-publik--resiliensi-pelosok) | Must |
| **FR-01c** | Penyimpanan draft isian otomatis di `localStorage` klien (*Auto-save*) dan deteksi rekoneksi jaringan | [DESIGN.md §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#7-pola-state-ui-loading--success--empty--error) | Must |
| **FR-02** | Validasi format NIK (16 digit) dan NIP (18 digit) serta validasi keunikan berbasis HMAC-SHA256 Blind Index | [SECURITY.md §7.1](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#71-enkripsi-kolom-nik--nip-aes-256-gcm--blind-indexing) | Must |
| **FR-02a** | Aturan Resubmission: NIK/NIP berstatus `MENUNGGU_VERIFIKASI` / `DISETUJUI` terkunci; status `DITOLAK` dapat mendaftar ulang sebagai baris data baru melalui *Partial Unique Index* | [SCHEMA.md §7.4](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#74-pendaftaran_tte-transaksi-utama-pendaftaran) | Must |
| **FR-03** | Auto-generate usulan username email dinas resmi (`@cirebonkota.go.id`) berdasarkan nama dan NIP jika `status_memiliki_email = false` | [ARCHITECTURE.md §6.1](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#61-modul-pendaftaran-publik--resiliensi-pelosok) | Must |

### 7.2 Modul Keamanan Akses & VPN Gateway (Admin Perimeter)
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-04a** | **Pemisahan Jalur Masuk Admin:** Rute autentikasi admin ditempatkan pada `/gateway/login` dan seluruh panel pada `/admin/*`, diisolasi dari rute publik terbuka | [ARCHITECTURE.md §1.1](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#11-diagram-topologi-kontainer--segmentasi-jaringan) | Must |
| **FR-04b** | **Inspeksi Jaringan VPN Gateway (`EnsureVpnAccess`):** Memverifikasi bahwa IP klien berasal dari Subnet CIDR VPN internal (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1/32`) atau membawa header gateway yang valid | [SECURITY.md §4.3](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#43-isolasi-jalur-admin-berbasis-vpn-gateway-zero-trust) | Must |
| **FR-04c** | **Stealth Mode Rejection:** Mengembalikan respon HTTP 404 (Not Found) untuk seluruh request non-VPN yang mencoba mengakses rute login/admin | [SECURITY.md §4.3](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#43-isolasi-jalur-admin-berbasis-vpn-gateway-zero-trust) | Must |

### 7.3 Modul Verifikasi Data & Manajemen Email Dinas (Tahap 1)
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-05** | Antrian verifikasi data FIFO untuk Superadmin dengan indikator data ASN dan usulan email | [DESIGN.md §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#7-pola-state-ui-loading--success--empty--error) | Must |
| **FR-06** | **Fitur Interaktif Penetapan Email Dinas Superadmin:**<br>1. Tombol "Gunakan Usulan" untuk menyalin `email_usulan` ke field input email dinas definitif.<br>2. Kemampuan mengedit nama username email secara manual pada field `@cirebonkota.go.id`.<br>3. Tombol aksi **"Rekap Email"** di sisi kanan field untuk mengonfirmasi dan merekap penetapan email definitif (`email_final_terverifikasi`) ke basis data | [ARCHITECTURE.md §6.2](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#62-modul-verifikasi-tahap-1--alur-rekap-email-dinas), [DESIGN.md §6.1](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#61-widget-interaktif-penetapan--rekap-email-dinas-superadmin) | Must |
| **FR-07** | Keputusan verifikasi data: Setujui (status `DISETUJUI`) atau Tolak (status `DITOLAK` wajib menyertakan `catatan_penolakan`) | [SCHEMA.md §7.4](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#74-pendaftaran_tte-transaksi-utama-pendaftaran) | Must |
| **FR-08** | Pencatatan otomatis identitas verifikator, waktu, dan IP address VPN (`verified_by`, `verified_at`, `verified_ip`) | [SECURITY.md §6.3](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#63-segregation-of-duty-mitigasi-pada-model-superadmin) | Must |

### 7.4 Modul Penerbitan Sertifikat TTE (Tahap 2)
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-09** | Input `tanggal_penerbitan` sertifikat TTE oleh Superadmin setelah email dinas aktif | [ARCHITECTURE.md §6.3](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#63-modul-verifikasi-tahap-2--penerbitan-sertifikat) | Must |
| **FR-10** | Kalkulasi otomatis `tanggal_kadaluarsa` = `tanggal_penerbitan + 2 tahun` | [SCHEMA.md §11.1](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#111-perhitungan-tanggal_kadaluarsa) | Must |
| **FR-11** | Pembuatan record `sertifikat_tte` dengan status awal `AKTIF` dan pencatatan jejak audit Tahap 2 (`verified_by_tahap2`) | [SCHEMA.md §7.6](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#76-sertifikat_tte-penerbitan--monitoring-siklus-hidup) | Must |

### 7.5 Modul Monitoring Siklus Hidup & Dashboard
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-12** | Task Scheduler harian otomatis memindai dan memperbarui status sertifikat (`WARNING_H3`, `EXPIRED`) | [ARCHITECTURE.md §10](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#10-scheduler--background-jobs) | Must |
| **FR-13** | Dashboard Analytics berkecepatan tinggi menampilkan counter Total ASN, Sudah Terbit, Antrian, dan Warning H-3 dari tabel agregator `dashboard_daily_stats` | [SCHEMA.md §7.7](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#77-dashboard_daily_stats-fast-aggregator) | Must |
| **FR-13b** | Widget Top 10 SKPD Dashboard berbasis persentase capaian adopsi pendaftar terhadap total populasi ASN (`total_asn`) dengan kontrol toggle pengurutan (persentase / volume) dan tombol pintas ke analitik penuh | [ARCHITECTURE.md §6.4](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#64-modul-dashboard-analytics--redis-fast-aggregator) | Must |
| **FR-14** | Aksi override manual Superadmin untuk mengubah status sertifikat menjadi `REVOKED` atau `PERLU_PERPANJANG` | [DESIGN.md §3](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#3-status--warna-tabel-pemetaan-skala-nasional) | Should |

### 7.6 Modul Pelaporan & Streaming Mass Export
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-15** | Laporan rekap pendaftaran ditolak lengkap dengan alasan penolakan, filter rentang tanggal dan SKPD | [ARCHITECTURE.md §7](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#7-modul-pelaporan--streaming-export) | Must |
| **FR-16** | Laporan rekap sertifikat terbit & email dinas definitif dengan filter status dan SKPD | [ARCHITECTURE.md §7](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#7-modul-pelaporan--streaming-export) | Must |
| **FR-17** | Ekspor laporan skala besar (ratusan ribu data) format CSV dan Excel via **Database Cursor Streaming (`streamDownload`)** tanpa buffering memori | [ARCHITECTURE.md §7](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#7-modul-pelaporan--streaming-export) | Must |
| **FR-17b** | Ekspor data capaian adopsi TTE seluruh SKPD berbasis persentase ke format CSV streaming dengan UTF-8 BOM, nama file bertanggal otomatis, dan sinkronisasi filter aktif | [ARCHITECTURE.md §7.2](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#72-ekspor-data-capaian-adopsi-skpd-format-csv) | Must |
| **FR-23** | Modul Analitik Capaian Adopsi TTE Seluruh SKPD (`/admin/capaian-skpd`): halaman dedikasi tanpa batas Top 10, 4 kartu KPI kota, tabel interaktif dengan progress bar warna, filter pencarian & kategori, sorting dinamis, serta paginasi | [ARCHITECTURE.md §6.6](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#66-modul-analitik-capaian-adopsi-tte-seluruh-skpd) | Must |

### 7.7 Modul Manajemen Data Master & Keamanan Akses
| ID | Kebutuhan Fungsional | Rujukan Teknis | Prioritas |
| :--- | :--- | :--- | :--- |
| **FR-18** | CRUD master data unit kerja SKPD (kode SKPD unik, nama SKPD, status aktif) | [SCHEMA.md §7.1](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#71-skpd-master-unit-kerja) | Must |
| **FR-19** | CRUD master data Jabatan dan Golongan (`master_jabatan_golongan`) untuk normalisasi data pendaftaran | [SCHEMA.md §7.2](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#72-master_jabatan_golongan-master-jabatan--golongan) | Must |
| **FR-20** | Manajemen akun internal Superadmin (aktivasi/deaktivasi akun) | [SCHEMA.md §7.3](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#73-users-iam-superadmin) | Must |
| **FR-21** | Autentikasi Superadmin wajib melalui jaringan VPN, menggunakan kata sandi kuat, progressive lockout, dan **TOTP MFA (Google Authenticator)** | [SECURITY.md §6.2](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#62-multi-factor-authentication-mfa) | Must |
| **FR-22** | Pencatatan seluruh aksi kritis secara asinkron ke tabel terpartisi `security_audit_logs` via Redis Queue | [SECURITY.md §8](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#8-lapisan-5-audit-trail-asinkron--observability) | Must |

---

## 8. Kebutuhan Non-Fungsional (Non-Functional Requirements)

| Kategori | Spesifikasi Standar Enterprise Skala Nasional |
| :--- | :--- |
| **Performa & Latensi** | - Akses halaman publik di jaringan 2G/3G: Waktu muat awal < 2.0 detik.<br>- Response time endpoint backend pada beban 1.000 *Concurrent Virtual Users*: < 300 ms.<br>- Query ringkasan statistik Dashboard Superadmin: < 50 ms (via Redis Caching). |
| **Efisiensi Memori Backend** | Penggunaan RAM proses PHP-FPM pada eksekusi streaming mass export 500.000 baris data: stabil di bawah 20 MB. |
| **Ketersediaan & Keandalan** | Target ketersediaan sistem 99.9% uptime, kontainer Docker terisolasi dengan restart policy `always`. |
| **Keamanan Data & Segmentasi Jaringan** | - Isolasi mutlak jalur admin via VPN Gateway (Subnet CIDR Filtering + Stealth HTTP 404).<br>- Enkripsi data pribadi NIK & NIP *at-rest* menggunakan AES-256-GCM.<br>- HMAC-SHA256 Blind Indexing untuk lookup tanpa pembobolan data massal.<br>- Kepatuhan penuh terhadap standar UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP). |
| **Resiliensi Infrastruktur** | Docker Isolated Named Volumes (`vendor_data`, `node_data`, `storage_data`) untuk mencegah I/O bind-mount lag di Cloud IDE (Antigravity) maupun Server Produksi. Jaringan basis data dan Redis terisolasi penuh (`internal: true`). |
| **Auditabilitas & Integritas** | Seluruh perubahan status, verifikasi, rekap email, IP koneksi VPN, dan aksi ekspor tercatat dalam *append-only* log terpartisi bulanan dengan indeks GIN pada payload JSONB. |

---

## 9. Matriks Keterhubungan Dokumen (Traceability Matrix)

| Kebutuhan Produk (PRD) | Spesifikasi Arsitektur | Spesifikasi Skema Data | Spesifikasi UI/UX | Spesifikasi Keamanan |
| :--- | :--- | :--- | :--- | :--- |
| **Pendaftaran & Low-Bandwidth (FR-01 s/d 04)** | [ARCHITECTURE §6.1](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#61-modul-pendaftaran-publik--resiliensi-pelosok) | [SCHEMA §7.4, §7.5](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#74-pendaftaran_tte-transaksi-utama-pendaftaran) | [DESIGN §5.1, §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#51-portal-publik--single-column-resilien-mobile-first) | [SECURITY §4.2, §5.2, §7.1](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#52-fast-timeout-recaptcha--local-math-captcha-engine) |
| **VPN Gateway & Isolasi Admin (FR-04a s/d 04c)** | [ARCHITECTURE §1.1, §1.2](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#11-diagram-topologi-kontainer--segmentasi-jaringan) | [SCHEMA §7.3](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#73-users-iam-superadmin) | [DESIGN §5.2, §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#52-panel-superadmin--sidebar--konten-padat-data) | [SECURITY §4.3, §6.2](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#43-isolasi-jalur-admin-berbasis-vpn-gateway-zero-trust) |
| **Verifikasi & Rekap Email (FR-05 s/d 08)** | [ARCHITECTURE §6.2](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#62-modul-verifikasi-tahap-1--alur-rekap-email-dinas) | [SCHEMA §7.4](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#74-pendaftaran_tte-transaksi-utama-pendaftaran) | [DESIGN §6.1, §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#61-widget-interaktif-penetapan--rekap-email-dinas-superadmin) | [SECURITY §6.3, §8](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#63-segregation-of-duty-mitigasi-pada-model-superadmin) |
| **Penerbitan & Monitoring (FR-09 s/d 14)** | [ARCHITECTURE §6.3, §6.4](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#63-modul-verifikasi-tahap-2--penerbitan-sertifikat) | [SCHEMA §7.6, §7.7](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#76-sertifikat_tte-penerbitan--monitoring-siklus-hidup) | [DESIGN §3, §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#3-status--warna-tabel-pemetaan-skala-nasional) | [SECURITY §8.1](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#81-logging-asinkron-via-redis-queue) |
| **Streaming Export (FR-15 s/d 17)** | [ARCHITECTURE §7](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#7-modul-pelaporan--streaming-export) | [SCHEMA §12](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#12-kebutuhan-pelaporan--pemetaan-kolom-export) | [DESIGN §6.4](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#64-modal-streaming-mass-export-streaming-export-modal) | [SECURITY §7.3](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#73-kontrol-export-laporan--anti-dos-memori) |
| **Master Data & IAM (FR-18 s/d 22)** | [ARCHITECTURE §6.5](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md#65-modul-manajemen-data-master) | [SCHEMA §7.1, §7.2, §7.3](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#71-skpd-master-unit-kerja) | [DESIGN §5.2, §7](file:///f:/sismon-tte-testing-V4/docs/DESIGN.md#52-panel-superadmin--sidebar--konten-padat-data) | [SECURITY §6.1, §6.2](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md#61-rbac--otorisasi-eksplisit-superadmin) |

---

## 10. Kontrol Dokumen

| Item | Keterangan |
| :--- | :--- |
| **Versi Dokumen** | **3.1.0-PROD** |
| **Tanggal Pembaruan** | 19 Agustus 2026 |
| **Dokumen Acuan** | [rencana_upgrade-v3.md](file:///f:/sismon-tte-testing-V4/rencana_upgrade-v3.md) |
| **Riwayat Perubahan** | • v1.0.0 – v1.3.0: Model peran 2 lapis, enkripsi NIK/NIP, penghapusan cek status publik.<br>• v2.1.0-PROD: Transformasi arsitektur skala nasional enterprise, standardisasi domain `@cirebonkota.go.id`, penambahan fitur interaktif rekap & edit email Superadmin, arsitektur low-bandwidth (penyederhanaan pendaftaran tanpa beban unggah berkas), dan Redis Queue Asynchronous Audit Logging.<br>• **v3.1.0-PROD:** Upgrade resmi ke Laravel 13 dan penerapan **Isolasi Jalur Admin Berbasis VPN Gateway (Zero-Trust Network Perimeter & Stealth 404 Block)**. |
| **Target Pembaca** | Pemilik Produk (Bidang Persandian), Lead Architect, Tim Pengembang Backend/Frontend, Tim Keamanan Siber & Infrastruktur |
