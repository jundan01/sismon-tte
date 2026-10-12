# PANDUAN LENGKAP PRESENTASI & SLIDE DECK (PPT)
## SISTEM MONITORING PENERBITAN TTE & PELAYANAN EMAIL DINAS RESMI
### PEMERINTAH KOTA CIREBON (DKIS KOTA CIREBON / BIDANG PERSANDIAN)

**Versi Sistem:** 3.2.0-PROD / Release V4.1 (Post-Audit Hardened Skala Nasional)  
**File Presentasi PowerPoint:** [`presentasi_sistem_tte.pptx`](file:///f:/sismon-tte-testing-V4/presentasi_sistem_tte.pptx)  
**File Presentasi HTML Interaktif:** [`presentasi_sistem_tte.html`](file:///f:/sismon-tte-testing-V4/presentasi_sistem_tte.html)  
**Dokumen Induk:** [`penjelasan_lengkap.md`](file:///f:/sismon-tte-testing-V4/penjelasan_lengkap.md)  
**Waktu Estimasi Paparan:** 15 – 20 Menit (+ Tanya Jawab Teknis)

---

## 🎯 PANDUAN PENGGUNAAN SLIDE DECK

Untuk mempresentasikan sistem ini kepada pimpinan, auditor, atau tim teknis, Anda memiliki **3 opsi fleksibel**:

1. **PowerPoint Asli (`.pptx`):**
   - Buka berkas [`presentasi_sistem_tte.pptx`](file:///f:/sismon-tte-testing-V4/presentasi_sistem_tte.pptx) menggunakan Microsoft PowerPoint, Google Slides, WPS Office, atau LibreOffice Impress.
   - Menggunakan format layar lebar 16:9 dengan tema IBM Carbon Dark & aksen Diskominfo Kota Cirebon.

2. **Presentasi HTML Interaktif (`.html`):**
   - Buka berkas [`presentasi_sistem_tte.html`](file:///f:/sismon-tte-testing-V4/presentasi_sistem_tte.html) di peramban web apa pun (Google Chrome, Microsoft Edge, Firefox, Safari).
   - Tekan **F** untuk mode layar penuh (*Fullscreen*).
   - Gunakan tombol panah **`←` / `→`** atau **`Space`** untuk berpindah slide.
   - Tekan **N** untuk membuka/menutup panel **Speaker Notes** (catatan pemateri).
   - Klik tombol **Print / PDF** untuk langsung mengekspor seluruh slide ke format PDF.

3. **Naskah Paparan (Markdown Outline):**
   - Gunakan naskah narasi slide-per-slide di bawah ini sebagai pedoman resmi saat membawakan presentasi.

---

## 📊 STRUKTUR SLIDE-PER-SLIDE & NASKAH PEMATERI (SPEAKER SCRIPT)

---

### SLIDE 1: COVER PRESENTASI
* **Kategori:** Dokumen Presentasi Resmi Skala Nasional
* **Judul Utama:** SISTEM MONITORING PENERBITAN TTE & PELAYANAN EMAIL DINAS RESMI
* **Subjudul:** Pemerintah Kota Cirebon — Dinas Komunikasi dan Informatika / Bidang Persandian (Transformasi Digital SPBE Berdaulat, Efisien, dan Aman - V4.1)
* **Metadata Box:**
  - Framework: Laravel 13 + PHP 8.3 FPM
  - Database: PostgreSQL 16 Alpine + Redis 7 Alpine
  - Security: Zero-Trust VPN Gateway Perimeter
  - Status: 3.2.0-PROD (Release V4.1 Post-Audit Hardened)

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Selamat pagi/siang bapak/ibu sekalian. Pada kesempatan ini, kami memaparkan Sistem Monitoring Penerbitan Tanda Tangan Elektronik (TTE) dan Pelayanan Email Dinas Resmi Pemerintah Kota Cirebon rilis V4.1. Sistem ini dibangun dengan arsitektur berstandar nasional, telah melalui tahap audit kode program menyeluruh pada 4 pilar, dan siap beroperasi di level produksi dengan perlindungan data pribadi penuh sesuai UU No. 27 Tahun 2022 dan standar BSSN."*

---

### SLIDE 2: LATAR BELAKANG & URGENSI SISTEM
* **Kategori:** 01. Pendahuluan & Regulasi
* **Poin Kunci:**
  1. **Mandat SPBE (Perpres No. 95/2018):** Seluruh naskah dinas kedinasan wajib beralih ke dokumen digital tersertifikasi. Diskominfo bertindak sebagai Registration Authority (RA) resmi bermitra dengan BSrE BSSN.
  2. **Kepatuhan UU PDP No. 27/2022:** Perlindungan ketat data kependudukan dan kepegawaian ASN (NIK 16 digit & NIP 18 digit) dengan enkripsi *at-rest*.
  3. **Prasyarat Email Dinas Resmi:** BSrE mewajibkan domain resmi `@cirebonkota.go.id` sebagai akun penerbitan sertifikat.
  4. **Evaluasi Masalah Ekosistem Lama:** Penggunaan Google Forms publik luar negeri, format email acak/konflik, dan sertifikat yang kadaluarsa senyap tanpa disadari pejabat penandatangan.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Latar belakang sistem ini bertumpu pada tiga fondasi hukum: Perpres SPBE 95/2018, UU PDP No 27/2022, dan regulasi BSrE BSSN. Sebelumnya, pendaftaran menggunakan Google Forms yang menyimpan NIK/NIP di server luar negeri tanpa enkripsi, serta tidak memiliki mekanisme peringatan dini saat sertifikat 2 tahun akan kedaluwarsa. Sistem V4.1 mentransformasi seluruh kelemahan tersebut."*

---

### SLIDE 3: MATRIKS MASALAH OPERASIONAL VS SOLUSI V4.1
* **Kategori:** 02. Analisis Masalah & Solusi
* **Tabel Komparasi:**
  - *Google Forms Publik* $\rightarrow$ **Portal Mandiri On-Premise:** Enkripsi AES-256-GCM + Blind Indexing PostgreSQL 16.
  - *Login Admin Terbuka* $\rightarrow$ **Zero-Trust VPN Gateway:** Rute `/gateway/login` khusus VPN Diskominfo + penolakan Stealth 404.
  - *Format Email Acak* $\rightarrow$ **Auto Email Generator:** Standardisasi `nama.nip@cirebonkota.go.id` + Widget Rekap Email AJAX 1-Klik.
  - *Beban Upload Berkas Fisik* $\rightarrow$ **Formulir Ramping Bebas Upload (< 5 KB):** Draft auto-save `sessionStorage` aman.
  - *Ekspor Data Crash (OOM)* $\rightarrow$ **Database Cursor Streaming:** Single query `leftJoin()->cursor()`, RAM stabil < 20 MB, zero N+1.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Matriks ini merangkum komparasi antara masalah operasional lama dengan solusi rekayasa pada versi 4.1. Seluruh titik rawan—seperti kebocoran NIK, serangan bot pada login admin, hingga server crash saat ekspor laporan ribuan baris—telah diatasi secara arsitektural."*

---

### SLIDE 4: TUJUAN SISTEM & TARGET KPI OPERASIONAL
* **Kategori:** 03. Sasaran Strategis
* **Poin Kunci:**
  - **Sasaran Bisnis:** Standardisasi satu pintu untuk 20 SKPD Kota Cirebon, pangkas birokrasi rekapitulasi email, pencegahan sertifikat kedaluwarsa dengan H-3 Warning Zone, dan transparansi antrean antar SKPD.
  - **4 Metrik KPI Utama:**
    - System Uptime Availability: **$\ge 99.9\%$**
    - Waktu Muat Formulir: **$< 2.0$ Detik** (Jaringan Seluler 3G)
    - Konsumsi RAM PHP Ekspor CSV: **$< 20$ MB** (Konstan)
    - Latensi Query Dashboard: **$< 25$ ms** (Redis 7 In-Memory Cache)

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Target kinerja operasional sistem kami tetapkan sangat tinggi: ketersediaan sistem minimal 99.9%, waktu muat form di bawah 2 detik untuk daerah jaringan minim, RAM PHP stabil di bawah 20 MB saat streaming ekspor, dan query analitik sub-milidetik di bawah 25 milidetik."*

---

### SLIDE 5: STACK TEKNOLOGI ENTERPRISE
* **Kategori:** 04. Arsitektur Teknologi
* **Komponen Stack:**
  1. **Backend:** Laravel 13 + PHP 8.3 FPM (OPcache JIT, Clean Domain Architecture).
  2. **Database:** PostgreSQL 16 Alpine (Native Monthly Partitioning, JSONB GIN Index, Partial Unique Index).
  3. **In-Memory Buffer:** Redis 7 Alpine (Fast Caching & Persistent Queue `audit-logs` AOF).
  4. **Edge & Frontend:** Nginx Hardened (TLS 1.3, Rate Limiting) + Carbon Flat 0px UI (100% Zero External CDN).
  5. **Infrastruktur:** Docker Compose V2 dengan Named Isolated Volumes (`vendor_data`, `node_data`, `storage_data`) dan segmentasi jaringan backend internal.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Teknologi yang dipilih mengedepankan performa, kedaulatan data, dan isolasi keamanan. Dengan PHP 8.3, PostgreSQL 16 terpartisi bulanan, antrian asinkron Redis 7, dan kontainerisasi non-root Docker, sistem memiliki ketahanan tinggi terhadap lonjakan transaksi konkuren."*

---

### SLIDE 6: ALUR TRANSAKSI DUA TAHAP (TWO-STAGE VERIFICATION)
* **Kategori:** 05. Alur Bisnis & Transaksi
* **3 Tahapan Siklus Hidup:**
  - **Tahap 1 (ASN Publik):** Pendaftaran mandiri di portal publik. Sistem otomatis menyusun usulan email dan mengenkripsi NIK/NIP. Terbit nomor referensi registrasi. Status: `MENUNGGU_VERIFIKASI`.
  - **Tahap 2 (Superadmin VPN):** Pemeriksaan keabsahan kepegawaian, klik "Gunakan Usulan" atau edit manual, lalu klik "Kunci & Rekap Email" via AJAX. Keputusan: `DISETUJUI` atau `DITOLAK`.
  - **Tahap 3 (BSrE BSSN & Cron):** Setelah email fisik aktif dan BSrE menerbitkan sertifikat, Superadmin input nomor sertifikat dan tanggal terbit. Sistem menghitung jatuh tempo (+2 Tahun). Status: `AKTIF`. Cron harian (00:01 WIB) otomatis memindai transisi `WARNING_H3` dan `EXPIRED`.
  - **Segregation of Duty:** Kolom verifikator Tahap 1 (`verified_by`) dan Tahap 2 (`verified_by_tahap2`) dicatat terpisah.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Model verifikasi 2-tahap menjamin verifikasi berjenjang yang akuntabel. Verifikator Tahap 1 memastikan keabsahan data kepegawaian dan mengunci email dinas. Setelah akun email dibuat dan BSrE menerbitkan sertifikat, Tahap 2 mencatat nomor sertifikat dan mengaktifkan monitoring siklus hidup harian."*

---

### SLIDE 7: PORTAL PUBLIK MANDIRI & RESILIENSI LOW-BANDWIDTH
* **Kategori:** 06. Inovasi Formulir Publik
* **Poin Kunci:**
  - **Formulir Ramping Bebas Upload:** Menghilangkan kewajiban upload berkas SK/KTP puluhan megabyte. Transaksi data teks murni sangat ringan (**< 5 KB**).
  - **100% Zero External CDN:** Seluruh font IBM Plex dan styling CSS dibundle lokal di dalam server, menjamin portal dapat dibuka di intranet tertutup.
  - **SessionStorage Auto-Save Aman:** Draft isian tersimpan per ketikan (`tte_registration_draft_temp`), aman saat sinyal seluler putus.
  - **Kepatuhan UU PDP:** NIK dan NIP **sengaja tidak disimpan di browser klien** untuk mencegah kebocoran pada komputer dinas bersama.
  - **Router CAPTCHA Cerdas:** Google reCAPTCHA v2/v3 diproteksi batas waktu 3 detik. Jika koneksi luar terputus, otomatis aktifkan **Local Math CAPTCHA** (HMAC-SHA256).

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Kami merancang formulir publik agar dapat diakses dengan sangat cepat oleh ASN di kantor kelurahan terpencil. Ukuran payload transaksi kurang dari 5 KB, aset 100% lokal tanpa CDN luar, dan auto-save draft ke sessionStorage mematuhi UU PDP karena mengecualikan NIK dan NIP dari storage browser."*

---

### SLIDE 8: AUTO EMAIL GENERATOR & AJAX RECAP WIDGET
* **Kategori:** 07. Otomasi Email Dinas
* **Poin Kunci:**
  - **Logika Generator Otomatis (`EmailGeneratorService`):** Membersihkan seluruh spasi, karakter aneh, dan gelar akademik/keagamaan ASN (Dr., H., S.Kom., M.Si.) dan menyusun usulan baku: `nama.nip@cirebonkota.go.id`.
  - **Panel Verifikasi Satu Klik:**
    1. Tombol *"Gunakan Usulan"* menyalin usulan baku secara instan.
    2. Superadmin dapat melakukan penyesuaian manual jika diperlukan.
    3. Tombol *"Kunci & Rekap"* via AJAX menyimpan `email_final_terverifikasi` dan menandai `is_email_recap_confirmed = true`.
  - **Efisiensi Waktu:** Memangkas durasi verifikasi dari 15 menit menjadi kurang dari 1 menit per pemohon.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Otomasi email dinas memecahkan masalah perbedaan format dan kesalahan ketik. Sistem secara otomatis membersihkan gelar dan membentuk format nama.nip@cirebonkota.go.id. Verifikator cukup klik satu tombol untuk mengunci email definitif."*

---

### SLIDE 9: ZERO-TRUST VPN GATEWAY & STEALTH 404 DEFENSE
* **Kategori:** 08. Keamanan Perimeter
* **Poin Kunci:**
  - **Penghapusan Login Publik:** Rute `/login` publik dihapus sepenuhnya untuk mengeliminasi target serangan brute-force.
  - **Pintu Masuk Khusus VPN:** Autentikasi dipindahkan ke `/gateway/login` dan `/admin/*`, diinspeksi oleh middleware `EnsureVpnAccess` terhadap subnet VPN Diskominfo (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1/32`).
  - **Respon Stealth Mode (HTTP 404 Not Found):** Jika diakses dari IP publik di luar VPN, server langsung menjawab dengan **HTTP 404 Not Found**, menyamarkan eksistensi rute admin dari pemindaian bot dan peretas.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Pada aspek perimeter, rute login admin tidak lagi dibuka di internet umum. Seluruh jalur administratif diisolasi ke dalam VPN Diskominfo. Jika ada pihak luar mencoba mengakses rute admin, sistem merespons dengan HTTP 404 Not Found murni, sehingga penyerang mengira halaman admin tidak ada."*

---

### SLIDE 10: ARSITEKTUR KEAMANAN BERLAPIS (DEFENSE-IN-DEPTH)
* **Kategori:** 09. Keamanan Berlapis
* **8 Lapisan Keamanan:**
  - **Layer 0-1 (Perimeter & Edge):** VPN Subnet CIDR, Nginx TLS 1.3, HSTS 2 Tahun, Rate Limit 5r/m & 30r/s.
  - **Layer 2-3 (Aplikasi & Kriptografi):** Sanitasi XSS, CSRF, AES-256-GCM kolom NIK/NIP, HMAC-SHA256 Blind Indexing.
  - **Layer 4-5 (IAM & Otorisasi):** RFC 6238 TOTP MFA (Google Authenticator), 8 Backup Recovery Codes terenkripsi, Step-Up Auth (`current_password`), dan Laravel Policies (`PendaftaranTtePolicy`).
  - **Layer 6-7 (Audit & Kontainer):** Redis Queue asinkron `audit-logs`, Partisi bulanan PostgreSQL, kontainer non-root (1000:1000), read-only rootfs, `internal: true` backend network.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Sistem dilindungi oleh 8 lapisan Defense-in-Depth. Jika satu lapisan mengalami gangguan, lapisan lainnya tetap melindungi data. Data sensitif NIK/NIP dienkripsi dengan AES-256-GCM at-rest, autentikasi admin diwajibkan TOTP MFA ganda, dan seluruh aktivitas dicatat dalam audit log terpartisi."*

---

### SLIDE 11: DATABASE CURSOR STREAMING MASS EXPORT ENGINE
* **Kategori:** 10. Efisiensi & Skalabilitas
* **Poin Kunci:**
  - **Masalah Ekspor Tradisional:** Memuat seluruh data ke memori PHP (`with()->get()`), memakan 500 MB – 1 GB RAM, memicu crash Out of Memory (OOM) dan badai kueri N+1.
  - **Solusi Cursor Streaming (V4.1):**
    - Kueri tunggal terpadu `DB::table()->leftJoin()->cursor()` (bebas N+1).
    - Mengalirkan baris demi baris via `php://output` langsung ke browser.
    - Konsumsi RAM PHP stabil **< 20 MB** bahkan untuk 1.000.000+ baris data.
    - Konfigurasi FastCGI dedicated `fastcgi_buffering off;` pada Nginx.
    - Disematkan header UTF-8 BOM (`\xEF\xBB\xBF`) agar data aksen/nama terbaca sempurna di Microsoft Excel.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Fitur ekspor massal direkayasa menggunakan Database Cursor Streaming. Tidak ada data yang menumpuk di memori server. Konsumsi RAM PHP terkunci stabil di bawah 20 MB untuk jutaan data, menjamin server tidak akan pernah mengalami crash kehabisan memori."*

---

---

### SLIDE 11b: DASHBOARD EKSEKUTIF & ANALITIK CAPAIAN ADOPSI SELURUH SKPD (V5.2)
* **Kategori:** 10b. Analitik Eksekutif & Pelaporan Pimpinan
* **Poin Kunci:**
  - **Eliminasi Bias Ukuran (*Size Bias*):** Pengurutan kepatuhan TTE berdasarkan rasio pendaftar terhadap total pegawai instansi ($(\text{Pendaftar} / \text{Total ASN}) \times 100\%$).
  - **Visibilitas Penuh Seluruh SKPD (`/admin/capaian-skpd`):** Menampilkan seluruh satuan kerja di Kota Cirebon tanpa batasan Top 10, lengkap dengan 4 Kartu KPI Kota (Rata-rata Adopsi Kota, Total Pegawai, Total Pendaftar, Total Sertifikat).
  - **Filter Cerdas & Progress Bar 4 Warna:** Indikator visual instan (Sangat Tinggi $\ge 75\%$, Tinggi $50-74.9\%$, Sedang $25-49.9\%$, Rendah $< 25\%$) untuk memetakan SKPD yang butuh pendampingan Diskominfo.
  - **Ekspor Laporan Khusus Pimpinan (CSV):** Mengalirkan berkas CSV bertanda waktu dengan UTF-8 BOM, siap dilampirkan langsung dalam rapat koordinasi Walikota dan Sekda.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Untuk pimpinan daerah, sistem kini dilengkapi modul Analitik Capaian Adopsi TTE Seluruh SKPD. Kita tidak hanya melihat 10 besar, tetapi seluruh instansi secara transparan. Dengan satu klik, data dapat diekspor ke CSV untuk bahan evaluasi berkala kepatuhan SPBE di lingkungan Pemerintah Kota Cirebon."*

### SLIDE 12: STRUKTUR BASIS DATA POSTGRESQL 16
* **Kategori:** 11. Struktur Data & Partisi
* **8 Skema Tabel Normalisasi:**
  1. `skpd`: Master 20 unit kerja instansi Kota Cirebon.
  2. `master_jabatan_golongan`: Standarisasi jabatan dan golongan ASN.
  3. `users`: Identity and Access Management Superadmin + TOTP Secret & Recovery Codes.
  4. `sessions`: Penyimpanan sesi peramban database terenkripsi.
  5. `pendaftaran_tte`: Transaksi utama pendaftaran, AES NIK/NIP, rekap email dinas.
  6. `sertifikat_tte`: Sertifikat BSrE aktif, jatuh tempo +2 tahun, status lifecycle.
  7. `dashboard_daily_stats`: Fast counter agregasi harian menyuplai Redis Cache.
  8. `security_audit_logs`: Audit trail terpartisi bulanan + default partition fallback.
* **Fitur Database Unggulan:** Partial Unique Indexing (`WHERE status != 'DITOLAK'`) dan Partisi Default Fallback (`security_audit_logs_default`).

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Struktur basis data PostgreSQL 16 dinormalisasi menjadi 8 tabel efisien tanpa beban tabel file lampiran. Fitur partial unique index memungkinkan ASN yang ditolak untuk mendaftar ulang secara mandiri tanpa terjadi tabrakan kunci unik."*

---

### SLIDE 13: HASIL REMEDIASI AUDIT KODE PROGRAM 4 PILAR
* **Kategori:** 12. Evaluasi Kualitas Kode
* **Status:** 100% Remediasi Tuntas (11 Temuan Selesai)
* **Ringkasan 4 Pilar:**
  - **Pilar I (Keamanan Data Pribadi):** PII NIK/NIP di browser dihapus, dialihkan ke `sessionStorage` aman (Kepatuhan UU PDP).
  - **Pilar II (IAM & Otorisasi):** Step-up auth `current_password` saat matikan MFA, 8 backup recovery codes terenkripsi, penegakan Laravel Policies.
  - **Pilar III (Basis Data & Kinerja):** Kueri streaming single join bebas N+1, partisi default fallback `security_audit_logs_default`.
  - **Pilar IV (Infrastruktur & Kode Bersih):** Penyelarasan regex email frontend-backend, DRY exception handler `$jsonResponder`, strict variable `${DB_PASSWORD:?...}` di Docker.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Seluruh 11 temuan audit kode program telah dituntaskan 100%. Mulai dari pengamanan storage browser klien, penguatan otorisasi controller via Laravel Policies, eliminasi badai kueri N+1, hingga hardening konfigurasi Docker dan Nginx."*

---

### SLIDE 14: PANDUAN OPERASIONAL & TROUBLESHOOTING
* **Kategori:** 13. Pemeliharaan & Deployment
* **Poin Kunci:**
  - **Perintah Deployment Cepat Docker:**
    `docker compose up -d --build`  
    `docker compose exec app php artisan migrate --seed --force`  
    `docker compose exec app php artisan config:cache`
  - **Crontab Host Scheduler:** `* * * * * ... php artisan schedule:run` untuk scan 00:01 WIB.
  - **Mitigasi Cepat Kode Error Operasional:**
    - `E-02`: Akses admin 404 $\rightarrow$ Hubungkan ke VPN Diskominfo.
    - `E-06`: Redis NOAUTH $\rightarrow$ Samakan `REDIS_PASSWORD` di `.env` dan `docker-compose.yml`.
    - `E-09`: MFA Lockout $\rightarrow$ Reset instan via Artisan Tinker terminal.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Panduan operasional merangkum prosedur deployment satu langkah dengan Docker Compose, konfigurasi cron task scheduler, dan matriks penanganan 12 kode error operasional dari E-01 sampai E-12 untuk memudahkan tim pemeliharaan IT Diskominfo."*

---

### SLIDE 15: PEMETAAN RUTE RESMI & KREDENSIAL DEFAULT
* **Kategori:** 14. Pemetaan Akses Resmi
* **Pemetaan Rute:**
  - Publik: `/` (Form Mandiri ASN), `/pendaftaran` (Submit).
  - VPN Only: `/gateway/login` (Login Admin).
  - VPN + Auth: `/admin/dashboard`, `/admin/verifikasi`, `/admin/penerbitan`, `/admin/export/csv`, `/admin/master/*`, `/admin/mfa/setup`.
* **Kredensial Default (Hasil Seeder):**
  - Superadmin Utama: `admin@cirebonkota.go.id`
  - Akun Penguji: `ajriajundan@gmail.com`
  - Operator Sandi: `operator@cirebonkota.go.id`
* *Catatan Keamanan:* Seluruh akun wajib mengaktifkan TOTP MFA dan mengganti password saat live di server produksi.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Slide ini memetakan seluruh rute publik dan administratif serta akun default yang disiapkan oleh seeder sistem untuk keperluan pengujian dan operasional awal."*

---

### SLIDE 16: KESIMPULAN & PENUTUP
* **Kategori:** Penutup
* **Status:** PRODUCTION READY
* **Pesan Utama:**
  Sistem Monitoring Penerbitan TTE & Pelayanan Email Dinas Resmi V4.1 Pemerintah Kota Cirebon telah memenuhi seluruh standar kepatuhan SPBE, UU PDP No. 27/2022, standar persandian BSSN, dan hasil audit kode program 4 pilar.
* **3 Keunggulan Pokok:**
  1. *Berdaulat & Terenkripsi:* Data tersimpan lokal di PostgreSQL 16 dengan proteksi AES-256-GCM.
  2. *Resiliensi Low-Bandwidth:* 100% Zero-CDN & form tanpa upload berkas < 5 KB.
  3. *Zero-Trust VPN Gateway:* Panel admin terisolasi penuh dari ancaman bot internet dengan Stealth 404.

> 🎙️ **Naskah Pemateri (Speaker Notes):**
> *"Sebagai kesimpulan, sistem ini telah siap 100% dioperasikan di level produksi untuk melayani seluruh aparatur sipil negara di lingkungan Pemerintah Kota Cirebon. Terima kasih atas perhatian bapak/ibu sekalian. Kami membuka sesi tanya jawab dan diskusi teknis."*

---

**Hak Cipta © 2026 Dinas Komunikasi dan Informatika / Bidang Persandian Pemerintah Kota Cirebon.**
