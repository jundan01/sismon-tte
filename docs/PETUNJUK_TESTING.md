# PANDUAN LENGKAP PENGUJIAN PRODUKSI LOKAL (LOCAL REAL TESTING ENVIRONMENT)
## SISTEM MONITORING TTE & PELAYANAN EMAIL DINAS (@cirebonkota.go.id)

**Versi Sistem:** 3.1.0-PROD (Laravel 13 + Zero-Trust VPN Gateway)  
**Tujuan Dokumen:** Petunjuk persiapan, kebutuhan dependensi resmi eksternal (API Google reCAPTCHA, SSL HTTPS, VPN Gateway), dan skenario pengujian komprehensif end-to-end seolah-olah sistem telah beroperasi secara resmi di lingkungan Pemerintah Kota Cirebon.  
**Lokasi Direktori:** `f:\sismon-tte-testing-V4`  

---

## 1. Persiapan Kebutuhan Resmi & API Luar (External Requirements)

Sebelum memulai pengujian, siapkan komponen resmi berikut:

### 1.1 Pembuatan Google reCAPTCHA v2 / v3 Resmi Gratis (Akun: `ajriajundan@gmail.com`)
Sistem dilengkapi dengan perlindungan bot berlapis menggunakan Google reCAPTCHA dengan fallback otomatis ke *Local Math CAPTCHA (HMAC-SHA256)*. Untuk menggunakan API resmi Google di localhost:

1. **Buka Konsol Google reCAPTCHA:**
   - Kunjungi: [https://www.google.com/recaptcha/admin/create](https://www.google.com/recaptcha/admin/create)
   - Login menggunakan akun Google Anda: **`ajriajundan@gmail.com`**.
2. **Pengisian Formulir Pendaftaran:**
   - **Label:** Sistem Monitoring TTE Cirebon (Localhost Testing)
   - **Tipe reCAPTCHA:** Pilih **reCAPTCHA v2 -> Kotak centang "Saya bukan robot"** (atau v3 berbasis skor).
   - **Domain yang Didaftarkan (Wajib):**
     - `localhost`
     - `127.0.0.1`
     - `::1`
   - **Pemilik:** `ajriajundan@gmail.com`
   - Centang **"Setujui Persyaratan Layanan reCAPTCHA"** dan klik **Kirim / Submit**.
3. **Penyalinan Kunci ke Konfigurasi Sistem:**
   - Salin **Kunci Situs (Site Key)** dan **Kunci Rahasia (Secret Key)** yang dihasilkan.
   - Buka berkas `.env` pada folder `sismon-tte-testing-V4` dan isi variabel berikut:
     ```env
     RECAPTCHA_SITE_KEY=6Ldxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     RECAPTCHA_SECRET_KEY=6Ldxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     ```
4. **Mekanisme Resiliensi (Fallback):**
   - Jika kunci belum diisi, koneksi internet terputus, atau Google API mengalami timeout (> 3 detik), sistem secara otomatis mengaktifkan **Local Math CAPTCHA** berbasis HMAC-SHA256 (5 menit masa berlaku) sehingga pengujian pendaftaran tidak akan pernah terhenti.

---

### 1.2 Sertifikat SSL Resmi Localhost (HTTPS Port 443 & 8443)
Sistem telah dilengkapi dengan sertifikat SSL 2048-bit SAN (*Subject Alternative Name*) yang siap pakai:
- **Lokasi Sertifikat:** `docker/nginx/certs/localhost.crt` dan `docker/nginx/certs/localhost.key`
- **Cakupan SAN:** `DNS:localhost`, `DNS:*.localhost`, `IP:127.0.0.1`, `IP:::1`
- **Konfigurasi Nginx:** Nginx telah dikonfigurasi untuk mendengarkan port 80 (HTTP) dan port 443 (HTTPS dengan protokol TLSv1.2 & TLSv1.3 serta HTTP Strict Transport Security / HSTS).

> **Catatan Pengujian Browser:**  
> Saat pertama kali membuka `https://localhost` atau `https://localhost:8443` di Google Chrome/Edge, klik **Advanced -> Proceed to localhost (unsafe)** untuk menyetujui sertifikat pengujian lokal.

---

### 1.3 Kredensial Superadmin & Basis Data Awal (Pre-Seeded Accounts)
Basis data pengujian telah diisi dengan akun resmi dan master data unit kerja SKPD:

| Email Akun | Peran (Role) | Kata Sandi Default | Keterangan |
| :--- | :---: | :---: | :--- |
| **`admin@cirebonkota.go.id`** | superadmin | `SuperAdmin2026!@#` | Superadmin Persandian DKIS Kota Cirebon |
| **`ajriajundan@gmail.com`** | superadmin | `Jundan12012005!@#` | **Akun Utama Penguji (Ajri Ajundan, S.Kom)** |
| **`operator@cirebonkota.go.id`** | superadmin | `Operator!2026#Sandi` | Operator Layanan TTE |

---

## 2. Langkah-Langkah Menjalankan Lingkungan Pengujian

### Cara A: Menjalankan Menggunakan Docker Compose (Direkomendasikan)
1. **Pastikan Docker Desktop aktif di komputer Anda.**
2. **Jalankan seluruh kontainer produksi:**
   ```bash
   cd f:\sismon-tte-testing-V4
   docker compose up -d --build
   ```
3. **Eksekusi Migrasi & Seeding Data Dummy (Opsional untuk Testing 50 ASN):**
   ```bash
   docker compose exec app php artisan migrate:fresh --seed
   ```
4. **Buka Aplikasi di Browser:**
   - Portal Publik: `https://localhost` (atau `http://localhost`)
   - Panel Admin (VPN): `https://localhost/gateway/login`

---

### Cara B: Menjalankan Menggunakan PHP & Web Server Lokal
1. **Pastikan PostgreSQL 16 & Redis 7 aktif di komputer lokal.**
2. **Jalankan migrasi database:**
   ```bash
   cd f:\sismon-tte-testing-V4
   php artisan migrate:fresh --seed
   ```
3. **Jalankan Worker Antrian & Scheduler (Opsional):**
   ```bash
   php artisan queue:work --queue=audit-logs,default
   ```
4. **Jalankan Web Server Built-in:**
   ```bash
   php artisan serve --port=8000
   ```
5. **Akses Portal:**
   - Form Publik: `http://127.0.0.1:8000/`
   - Form Admin: `http://127.0.0.1:8000/gateway/login`

---

> **Catatan Sebelumnya:**
> Skenario pengujian 1 s/d 7 di bawah ini disusun untuk validasi end-to-end V3.1.0 (23 Agustus 2026). Skenario telah diperbarui per 24 Agustus 2026 untuk mencakup verifikasi kepatuhan pasca-audit: penyimpanan draft tanpa PII NIK/NIP di `sessionStorage`, verifikasi kata sandi saat nonaktifkan MFA (step-up auth), persistensi recovery codes terenkripsi, serta ekspor unbuffered streaming bebas N+1 query.

---

## 3. Skenario Pengujian Komprehensif (End-to-End Test Matrix)

Lakukan pengujian sesuai dengan alur operasional resmi berikut:

### Skenario 1: Pendaftaran Mandiri ASN (Portal Publik)
1. Buka halaman utama: `http://localhost/` atau `http://127.0.0.1:8000/`.
2. Verifikasi fitur antarmuka:
   - Status Indikator: `[● SISTEM ONLINE (DISAGREE TO LATENCY)]`
   - Draft Auto-Save Resilien: Ketik nama/SKPD/jabatan, lalu buka tab baru dalam satu sesi browser; draft form tetap tersimpan di `sessionStorage` (`tte_registration_draft_temp`).
   - Kepatuhan PDP: Periksa `sessionStorage` via DevTools; field `nik` dan `nip` terbukti **TIDAK PERNAH disimpan di browser**.
   - Pilihan Unit Kerja: Pilih salah satu dari 20 SKPD Kota Cirebon.
   - Pilihan Jabatan & Golongan dari data master.
   - Toggle Status Email Dinas:
     - Jika "Belum Memiliki": Kolom Usulan Email otomatis terisi format `nama.nip@cirebonkota.go.id` (gelar akademik dan keagamaan dibersihkan otomatis).
     - Jika "Sudah Memiliki": Masukkan email dinas aktif.
   - Jawab CAPTCHA (reCAPTCHA atau Local Math).
3. Klik **"Kirim Formulir Pendaftaran"** -> Sistem memberikan Nomor Registrasi resmi (contoh: `TTE-20260824-XXXXXX`), membersihkan draft di browser, dan menampilkan status pendaftaran.

---

### Skenario 2: Isolasi Rute Admin & VPN Gateway Perimeter Defense
1. **Uji Akses Ilegal dari Luar VPN:**
   - Coba buka URL admin lama: `http://localhost/login` -> Menghasilkan **404 Not Found**.
   - Coba akses langsung URL dashboard: `http://localhost/admin/dashboard` tanpa login -> Menghasilkan **404 Not Found** jika dari IP publik atau redirect jika dari subnet terpercaya.
2. **Uji Akses Sah melalui VPN Gateway:**
   - Buka `http://localhost/gateway/login` -> Halaman login Superadmin terbuka dengan badge hijau:
     `[● JARINGAN VPN GATEWAY TERVERIFIKASI]`.
   - Masukkan email: `ajriajundan@gmail.com` dan kata sandi: `Jundan12012005!@#` (atau `admin@cirebonkota.go.id` / `SuperAdmin2026!@#`).
   - Klik **"Masuk ke Panel Kontrol"** -> Berhasil dialihkan ke Dashboard Admin.

---

### Skenario 3: Verifikasi Permohonan Pemohon (Tahap 1 & Policy Enforcement)
1. Pada sidebar, klik **"Verifikasi Tahap 1"** (`/admin/verifikasi`).
2. Periksa antrian pemohon dengan urutan **FIFO (First In First Out)** via tautan Nomor Referensi Resmi.
3. Klik tombol **"Periksa"** pada salah satu pendaftaran pemohon:
   - Panel detail menyajikan data lengkap identitas kepegawaian ASN.
4. Klik **"Kunci & Rekap"** email dinas jika akun email telah siap di server mail.
5. Klik **"Setujui Permohonan"** -> `PendaftaranTtePolicy` memverifikasi otorisasi dan status pendaftaran berubah menjadi `DISETUJUI`.
6. Coba tolak pendaftaran lain dengan menyertakan **Catatan Penolakan** -> Status berubah menjadi `DITOLAK` (ASN dapat mendaftar ulang dengan perbaikan berkas).

---

### Skenario 4: Penerbitan Sertifikat Elektronik (Tahap 2)
1. Pada sidebar, klik **"Penerbitan Tahap 2"** (`/admin/penerbitan`).
2. Pada tab **"Antrian Penerbitan (Tahap 2)"**, cari permohonan yang telah berstatus `DISETUJUI`.
3. Klik **"Terbitkan Sertifikat"**:
   - Masukkan Nomor Sertifikat BSrE dan Tanggal Penerbitan.
   - Sistem secara otomatis menghitung masa berlaku **2 Tahun Penuh** (`tanggal_kadaluarsa = tanggal_penerbitan + 2 tahun`).
4. Klik **"Simpan & Aktifkan Sertifikat"** -> `SertifikatTtePolicy` memverifikasi izin dan data berpindah ke tab **"Daftar Sertifikat TTE Terbit"** dengan status `AKTIF`.

---

### Skenario 5: Fast Dashboard Analytics & Redis Cache (< 50ms)
1. Klik menu **"Dashboard Transaksi"** (`/admin/dashboard`).
2. Periksa 4 indikator metrik utama:
   - *Total ASN Terdaftar*
   - *Antrian Verifikasi Berkas (Tahap 1)*
   - *Sertifikat TTE Terbit (Aktif)*
   - *Peringatan Kedaluwarsa (Zona H-3)*
3. Periksa grafik distribusi per SKPD dan antrian verifikasi terdepan.
4. Klik tombol **"Sinkronkan Metrik Sekarang"** untuk memicu agregasi fast counter harian ke Redis.

---

### Skenario 6: Ekspor Massal CSV Berkecepatan Tinggi (Unbuffered FastCGI & Zero N+1)
1. Pada sidebar, klik **"Ekspor Massal CSV"** atau tombol **"Ekspor Laporan CSV"** (`/admin/export/csv`).
2. Pilih filter tanggal pendaftaran dan status sertifikat, lalu klik **"Unduh Berkas CSV (Stream)"**.
3. Sistem mengalirkan data via `DB::table('pendaftaran_tte')->leftJoin(...)` dalam 1 kueri terpadu dengan header BOM UTF-8 (`\xEF\xBB\xBF`) dan unbuffered proxy Nginx.
4. Verifikasi bahwa download langsung dimulai tanpa jeda buffering dan karakter khusus ASN langsung rapi saat dibuka di Microsoft Excel.

---

### Skenario 7: Setup TOTP MFA, Recovery Codes & Step-Up Deaktivasi
1. Pada sidebar, klik **"Akun Superadmin & MFA"** (`/admin/master/users`).
2. Klik tombol **"Aktifkan Google Authenticator (TOTP MFA)"** pada akun `ajriajundan@gmail.com`.
3. Pindai QR Code menggunakan aplikasi Google Authenticator / Aegis di ponsel Anda.
4. Masukkan 6 digit kode token dan konfirmasi -> Sistem menyimpan 8 kode pemulihan terenkripsi ke database (`mfa_recovery_codes`).
5. **Uji Step-Up Authentication Nonaktifkan MFA:**
   - Coba klik **"Nonaktifkan MFA"** tanpa memasukkan kata sandi saat ini -> Sistem menolak dan menampilkan validasi error kata sandi wajib diisi.
   - Masukkan kata sandi yang benar -> MFA berhasil dinonaktifkan dan audit log `DISABLE_MFA_USER` tercatat ke database.

---

---

### Skenario 8: Pengujian Analitik Capaian Adopsi TTE Seluruh SKPD (`/admin/capaian-skpd`)
1. Klik menu navigasi **"Capaian Adopsi SKPD"** pada sidebar, atau klik tombol **"Lihat Seluruh SKPD & Ekspor"** pada widget Top 10 di Dashboard utama.
2. Periksa tampilan 4 Kartu KPI Ringkasan Kota:
   - *Rata-rata Capaian Kota (%)*
   - *Total Pegawai Terdaftar*
   - *Total Pendaftar TTE*
   - *Total Sertifikat Terbit*
3. **Uji Filter Pencarian:** Masukkan nama instansi (misal: "Kesehatan" atau "DKIS") pada kolom pencarian dan klik Cari -> tabel menampilkan hanya SKPD yang sesuai.
4. **Uji Filter Kategori:** Pilih kategori capaian (misal: "Sangat Tinggi (≥75%)" atau "Rendah (<25%)") -> tabel memfilter baris data sesuai rentang persentase.
5. **Uji Pengurutan:** Klik header kolom tabel atau gunakan kontrol sorting untuk mengurutkan data berdasarkan persentase tertinggi/terendah.
6. Periksa indikator progress bar dan badge kategori warna (Hijau Emerald, Biru Cyan, Amber Kuning, Slate Abu-abu).

---

### Skenario 9: Pengujian Ekspor Data Capaian Adopsi SKPD Format CSV (`/admin/capaian-skpd/export`)
1. Pada halaman Capaian Adopsi SKPD, klik tombol hijau **"Ekspor CSV"** (atau klik tautan **"Ekspor Capaian SKPD (CSV)"** di sidebar grup Pelaporan).
2. Browser langsung mengunduh berkas dengan penamaan otomatis `capaian_adopsi_tte_skpd_YYYYMMDD_HHmmss.csv`.
3. Buka file CSV di Microsoft Excel atau Text Editor:
   - Verifikasi bahwa baris header dan data menggunakan encoding UTF-8 dengan BOM (`\xEF\xBB\xBF`) sehingga karakter khusus dan simbol nama SKPD tampil rapi tanpa *garbled characters*.
   - Verifikasi kolom data: *No, Nama SKPD, Singkatan, Total Pegawai, Total Pendaftar TTE, Total Sertifikat Terbit, Persentase Adopsi (%), Status Capaian*.
4. **Uji Sinkronisasi Filter:** Terapkan filter kategori "Rendah" lalu klik Ekspor CSV -> periksa bahwa data di dalam CSV yang diunduh hanya memuat instansi dengan kategori "Rendah".

---

## 4. Checklist Verifikasi Kelayakan Testing (Go-Live Ready)

- [ ] Google reCAPTCHA key terpasang di `.env` (atau fallback Local Math CAPTCHA aktif).
- [ ] HTTPS / SSL 2048-bit SAN aktif tanpa kegagalan handshake.
- [ ] Login admin berhasil dengan akun `admin@cirebonkota.go.id` atau `ajriajundan@gmail.com`.
- [ ] Seluruh data master SKPD & Jabatan termuat sempurna di tabel verifikasi dan dashboard.
- [ ] Perlindungan VPN Gateway menolak akses non-VPN dengan respon 404 Stealth.
- [ ] Verifikasi Tahap 1, Penerbitan Tahap 2, dan Ekspor CSV berjalan mulus tanpa error.
- [ ] Kepatuhan UU PDP: NIK/NIP tidak tersimpan di `localStorage` / `sessionStorage` browser.
- [ ] Nonaktifkan MFA mewajibkan konfirmasi password aktif (`current_password`).
- [ ] Modul Capaian Adopsi SKPD menampilkan seluruh data SKPD aktif tanpa batasan Top 10.
- [ ] Fitur Ekspor CSV Capaian SKPD menghasilkan file valid UTF-8 BOM dan sinkron dengan filter aktif.
- [ ] Seluruh 70 Automated Tests passed 100% (`php artisan test` atau `php artisan test --filter=CapaianSkpdTest`).
