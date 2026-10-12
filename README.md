# Sistem Monitoring Penerbitan TTE & Pelayanan Email Dinas Resmi

[![PHP](https://img.shields.io/badge/PHP-8.2%20%2F%208.3-777bb4.svg?logo=php&logoColor=white)]()
[![Framework](https://img.shields.io/badge/Framework-Laravel%2012-ff2d20.svg?logo=laravel&logoColor=white)]()
[![Database](https://img.shields.io/badge/Database-PostgreSQL%2016-336791.svg?logo=postgresql&logoColor=white)]()
[![Cache & Queue](https://img.shields.io/badge/Cache%20%26%20Queue-Redis%207-dc382d.svg?logo=redis&logoColor=white)]()
[![Container](https://img.shields.io/badge/Deployment-Docker%20Compose-2496ed.svg?logo=docker&logoColor=white)]()

Aplikasi web layanan internal yang dikembangkan untuk **Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon (DKIS Kota Cirebon) / Bidang Persandian**. Sistem ini mengelola alur operasional permohonan sertifikat Tanda Tangan Elektronik (TTE) Balai Sertifikasi Elektronik (BSrE), standardisasi akun email dinas resmi pemerintah daerah (`@cirebonkota.go.id`), verifikasi berkas berjenjang, pemantauan siklus masa berlaku sertifikat, serta analitik capaian adopsi TTE di lingkungan Satuan Kerja Perangkat Daerah (SKPD) Pemerintah Kota Cirebon.

---

## 🏛️ Fitur Utama

- **Pendaftaran Mandiri ASN (Paperless):**  
  Formulir permohonan berbasis data teks terstruktur yang ringan dan ramah jaringan berkecepatan rendah (*low-bandwidth*), tanpa kewajiban unggah berkas fisik. Dilengkapi pemilih interaktif (searchable dropdown) untuk unit kerja, jabatan, dan golongan, serta penyimpanan draft lokal temporer yang aman.
  
- **Generator Email Dinas Resmi:**  
  Penyusunan usulan format akun email dinas baku (`@cirebonkota.go.id`) secara otomatis dengan eliminasi gelar akademik, gelar keagamaan, dan tanda baca sesuai standar identitas kepegawaian.

- **Verifikasi & Validasi Berjenjang:**  
  Antrian terstruktur (*First-In, First-Out*) bagi verifikator untuk memeriksa kesesuaian data kepegawaian, memvalidasi deskripsi penempatan kerja, menetapkan email dinas definitif dengan proteksi anti-duplikasi, serta mencatat catatan perbaikan secara transparan.

- **Manajemen Siklus Hidup Sertifikat (Lifecycle Management):**  
  Pencatatan masa berlaku sertifikat resmi 2 tahun dengan pemantauan otomatis berkala untuk mendeteksi sertifikat yang mendekati kedaluwarsa (peringatan dini H-7) hingga kedaluwarsa.

- **Analitik & Rekapitulasi Capaian SKPD:**  
  Dashboard statistik capaian adopsi TTE terhadap populasi ASN pada seluruh SKPD di lingkungan Pemerintah Kota Cirebon, dilengkapi filter kepatuhan, pengurutan dinamis, dan ekspor laporan format CSV memory-safe.

- **Keamanan Data & Segmentasi Akses:**  
  Penerapan proteksi enkripsi untuk data identitas sensitif, proteksi bot bertingkat, pembatasan akses administratif berbasis jaringan resmi terproteksi, dan pencatatan audit log keamanan untuk seluruh aktivitas operasional.

---

## 💻 Kebutuhan Sistem (Prerequisites)

- **PHP:** Versi 8.2 atau 8.3 (dengan ekstensi `pdo_pgsql`, `redis`, `gd`, `zip`, `bcmath`, `curl`, `mbstring`, `openssl`)
- **Web Server:** Nginx 1.24+ / Apache 2.4+
- **Database:** PostgreSQL 16+
- **Cache & Queue:** Redis 7+
- **Manajer Paket:** Composer 2.7+ & Node.js 20+ (opsional untuk build aset)
- **Atau Container Engine:** Docker 24+ & Docker Compose v2.20+ (direkomendasikan)

---

## 🚀 Panduan Menjalankan Sistem

### Opsi A: Menggunakan Docker Compose (Direkomendasikan)

1. **Salin file konfigurasi environment:**
   ```bash
   cp .env.example .env
   ```
   *Sesuaikan parameter konfigurasi database, Redis, URL aplikasi, dan kunci keamanan pada file `.env`.*

2. **Build dan jalankan seluruh container:**
   ```bash
   docker compose up -d --build
   ```

3. **Inisialisasi aplikasi:**
   ```bash
   # Generate Application Key
   docker compose exec app php artisan key:generate

   # Jalankan migrasi basis data dan master data
   docker compose exec app php artisan migrate --seed

   # Buat symbolic link storage
   docker compose exec app php artisan storage:link
   ```

4. **Optimasi cache produksi (opsional):**
   ```bash
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   ```

---

### Opsi B: Instalasi Manual (Host / Non-Docker)

1. **Pasang dependensi PHP:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Konfigurasi basis data & environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan storage:link
   ```

3. **Jalankan migrasi:**
   ```bash
   php artisan migrate --seed
   ```

4. **Jalankan worker antrian & scheduler:**
   ```bash
   # Background Queue Worker
   php artisan queue:work redis --queue=audit-logs,default

   # Task Scheduler (atur via cron server setiap menit)
   php artisan schedule:run
   ```

---

## 📁 Struktur Direktori Singkat

```
├── app/
│   ├── Domain/              # Logika bisnis terstruktur (Analytics, Master, Pendaftaran, Sertifikat)
│   ├── Http/                # Controllers, Requests validasi, dan Middleware keamanan
│   ├── Jobs/                # Pemrosesan background asinkron (Audit logs & scheduler status)
│   ├── Models/              # Model Eloquent data sistem
│   └── Services/            # Layanan enkripsi, email generator, dan captcha
├── config/                  # Konfigurasi sistem
├── database/                # Skema migrasi dan data awal (seeder SKPD, jabatan, golongan)
├── docker/                  # Konfigurasi container Nginx dan PHP-FPM
├── public/                  # Aset statis aplikasi (tanpa dependensi CDN eksternal)
├── resources/views/         # Antarmuka tampilan Blade
├── routes/                  # Definisi rute aplikasi
└── tests/                   # Suite pengujian otomatis (Unit & Feature Tests)
```

---

## 🛡️ Keamanan & Pelaporan Kerentanan

Aplikasi ini mengelola data kepegawaian aparatur pemerintahan. Jika Anda menemukan potensi kerentanan keamanan (*security vulnerability*), mohon untuk **tidak** mempublikasikannya melalui *public issue tracker*.

Silakan laporkan secara bertanggung jawab (*responsible disclosure*) kepada Tim Keamanan Informasi / Bidang Persandian melalui saluran resmi:
- **Email:** `persandian@cirebonkota.go.id` / `diskominfo@cirebonkota.go.id`
- **Instansi:** Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon

---

## 📜 Lisensi & Hak Cipta

Hak Cipta © 2026 **Dinas Komunikasi, Informatika, dan Statistik Kota Cirebon (DKIS Kota Cirebon) / Bidang Persandian**.  
Seluruh hak cipta dilindungi undang-undang.
