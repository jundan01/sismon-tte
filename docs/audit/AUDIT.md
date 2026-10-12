# PANDUAN AUDIT KODE PROGRAM APLIKASI (UNTUK AI AGENT)

Panduan ini dirancang untuk menginstruksikan AI Agent agar dapat melakukan audit, analisis, dan memberikan rekomendasi perbaikan pada repositori kode tanpa melakukan modifikasi langsung pada berkas sumber. Hasil audit wajib dikeluarkan dalam bentuk laporan HTML interaktif yang bersih, modern, dan mudah dibaca oleh manusia.

---

## 1. PERAN & BATASAN OPERASIONAL (OPERATIONAL CONSTRAINTS)

AI Agent wajib mematuhi batasan operasional berikut:
1. **Mode Hanya Membaca (Read-Only Mode):** Dilarang keras melakukan penulisan, modifikasi, penggantian, atau penghapusan berkas kode sumber asli di dalam repositori.
2. **Mode Penasihat (Advisory Mode):** Tugas utama adalah menganalisis dan mendokumentasikan temuan serta solusi perbaikan.
3. **Integritas Berkas Mandiri (Self-Contained File):** Hasil akhir analisis harus diekspor menjadi satu berkas HTML mandiri bernama `laporan_audit.html` di direktori yang ditentukan. Seluruh kode CSS dan Javascript pembantu wajib di-embed langsung ke dalam berkas HTML tanpa dependensi eksternal (CDN/internet) agar dapat dibuka secara offline.
4. **Keamanan Konten Laporan (HTML Escaping):** Seluruh contoh potongan kode/konfigurasi asli yang dimasukkan ke dalam berkas laporan HTML wajib melalui proses *escaping* karakter khusus (seperti `<` menjadi `&lt;`, `>` menjadi `&gt;`, dan `&` menjadi `&amp;`) untuk mencegah kerusakan visual layout HTML pada browser.

---

## 2. RUANG LINGKUP & EKSKLUSI (SCOPE & EXCLUSIONS)

Untuk mengoptimalkan efisiensi analisis dan mencegah pembacaan data yang tidak relevan, AI Agent wajib mengecualikan folder dan berkas berikut dari proses audit:
1. **Folder Dependensi:** `node_modules/`, `vendor/`, `bower_components/`, `packages/` (eksternal).
2. **Folder Metadata & Sistem:** `.git/`, `.github/`, `.idea/`, `.vscode/`, `.svn/`.
3. **Berkas Build & Bundling:** `dist/`, `build/`, `out/`, `target/`, `*.pyc`.
4. **Berkas Kunci Dependensi:** `package-lock.json`, `yarn.lock`, `composer.lock`, `pnpm-lock.yaml`, `Gemfile.lock`.
5. **Aset Statis & Media:** Berkas gambar (`.png`, `.jpg`, `.jpeg`, `.gif`, `.svg`, `.ico`), video/audio, dan file font (`.ttf`, `.woff`, `.woff2`, `.eot`).
6. **Berkas Konfigurasi Sensitif Lokal:** `.env`, `.env.local` (cukup audit keberadaan filenya di repositori, jangan muat/tampilkan isinya).

---

## 3. PILAR & CAKUPAN AUDIT

AI Agent harus memeriksa dan menganalisis 4 pilar utama berikut:

### A. Keamanan (Security)
*   **OWASP Top 10:** Deteksi celah keamanan populer seperti SQL Injection, Cross-Site Scripting (XSS), Broken Authentication, SSRF, XML External Entities (XXE), dll.
*   **Data Sensitif:** Pemindaian terhadap kunci API (*API Keys*), sandi (*credentials*), token, atau informasi rahasia yang tertulis langsung di dalam kode (*hardcoded*).
*   **Validasi & Sanitasi:** Memastikan semua input pengguna divalidasi dan disanitasi sebelum diproses atau disimpan.

### B. Struktur & Kualitas Kode (Structure & Code Quality)
*   **Modularitas & Pola Desain:** Menganalisis apakah kode modular atau terlalu menumpuk (*monolithic*). Rekomendasikan pola seperti *MVC*, *Repository Pattern*, atau *Clean Architecture*.
*   **Clean Code:** Memeriksa tingkat keterbacaan kode, redundansi (prinsip *DRY - Don't Repeat Yourself*), penamaan variabel/fungsi, serta kedalaman logika bersarang (*nested loops/conditions*).
*   **Penanganan Error:** Memeriksa apakah aplikasi memiliki manajemen *exception/error handling* yang aman dan tidak membocorkan informasi sistem kepada pengguna.

### C. Database
*   **Optimasi Query:** Mengidentifikasi query SQL yang lambat, tidak efisien (seperti pola query N+1), atau tidak menggunakan indeks.
*   **Manajemen Koneksi:** Memastikan koneksi database dikelola melalui *connection pooling* dan ditutup dengan benar setelah digunakan untuk mencegah kebocoran koneksi.
*   **Desain Skema:** Meninjau kesesuaian tipe data dan struktur relasi antar tabel berdasarkan file migrasi atau ORM yang ada.

### D. Infrastruktur & Konfigurasi (Infrastructure & Configuration)
*   **Docker & Container:** Menganalisis `Dockerfile` atau `docker-compose.yml` untuk memastikan optimasi *multi-stage build*, penggunaan base image minimal, dan tidak berjalan sebagai user `root`.
*   **Konfigurasi Web Server:** Memeriksa konfigurasi server (seperti Nginx/Apache) terkait keamanan header (HSTS, CSP, X-Frame-Options) dan SSL/TLS.
*   **Manajemen Environment:** Memastikan variabel lingkungan sensitif dikelola melalui berkas `.env` atau integrasi dengan *Secrets Manager*, bukan disimpan di repositori.

---

## 4. ALUR KERJA ANALISIS AI AGENT

AI Agent harus mengikuti alur kerja sistematis berikut:
1. **Pemindaian & Pemetaan Awal (Scanning & Initial Mapping):**
   * Membaca struktur direktori (dengan memperhatikan aturan eksklusi ruang lingkup) untuk mengidentifikasi bahasa pemrograman, framework, dan pustaka yang digunakan.
   * Jika repositori berukuran besar (skala menengah ke atas), jalankan **Strategi Analisis Bertahap (Incremental Batching & Prioritization)**:
     1. **Tahap Konfigurasi:** Analisis berkas manifes/konfigurasi utama terlebih dahulu (seperti `package.json`, `pom.xml`, `composer.json`, `Dockerfile`, atau konfigurasi DB) untuk memahami profil sistem.
     2. **Tahap Routing:** Petakan alur masuk data utama (*entrypoints*) melalui berkas rute (*router / route definitions*).
     3. **Tahap Logika Bisnis:** Prioritaskan audit pada berkas *Controller*, *Middleware*, dan *Service/Repository* yang menangani input pengguna langsung atau transaksi database.
     4. **Tahap Batasan Konteks:** Hindari membaca seluruh codebase sekaligus dalam satu waktu guna mencegah terlampauinya batas konteks memori (*Context Window Limit*). Lakukan analisis berkas secara bertahap (per modul/fitur).
2. **Kategorisasi & Penilaian Dampak (Severity Level Matrix):** Setiap masalah yang ditemukan harus dikategorikan berdasarkan pilar audit dan diberikan tingkat keparahan berdasarkan kriteria berikut:
   *   🔴 **Critical (Kritis):**
       *   *Kriteria:* Celah keamanan yang dapat dieksploitasi langsung (SQL Injection, XSS, CSRF, SSRF, RCE, kebocoran API Keys/sandi sensitif), kebocoran memori parah yang meruntuhkan sistem, atau kegagalan kepatuhan hukum fatal.
       *   *Tindakan:* Harus diselesaikan sebelum rilis produksi.
   *   🟡 **Warning/Major (Peringatan):**
       *   *Kriteria:* Kualitas kode buruk yang berdampak langsung pada performa (seperti query database N+1, tidak adanya indeks pada kolom pencarian utama), penutupan resource/koneksi database yang tidak aman, atau penyimpangan arsitektur standar.
       *   *Tindakan:* Harus direncanakan untuk diperbaiki pada iterasi pengembangan berikutnya.
   *   🔵 **Info/Minor (Informasi):**
       *   *Kriteria:* Keterbacaan kode (*code smell*), dokumentasi/komentar fungsi yang kurang lengkap, atau ketidakpatuhan ringan terhadap standar formatting/linting bahasa pemrograman.
       *   *Tindakan:* Perbaikan opsional/minor untuk menjaga kerapian basis kode.
3. **Penyusunan Solusi:** Menyusun rekomendasi langkah-demi-langkah dan contoh kode perbaikan (sebelum vs sesudah) dengan melakukan *HTML escaping* pada potongan kode yang ditampilkan.
4. **Pembuatan Laporan HTML:** Menyusun seluruh data temuan ke dalam template HTML yang telah disediakan di bawah ini.

---

## 5. TEMPLATE HTML LAPORAN AUDIT

AI Agent harus menghasilkan file `laporan_audit.html` dengan struktur dan gaya (CSS) seperti template di bawah ini. Kode CSS dan Javascript pembantu harus di-*embed* langsung di dalam berkas HTML agar laporan bersifat portabel (cukup satu berkas HTML tanpa dependensi eksternal).

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Audit Kode Program Aplikasi</title>
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-indigo: #6366f1;
            --accent-emerald: #10b981;
            --severity-critical: #ef4444;
            --severity-warning: #f59e0b;
            --severity-info: #3b82f6;
            --border-color: #334155;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            padding: 2rem;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            padding: 2.5rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            background: linear-gradient(to right, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .meta-item strong {
            color: var(--text-primary);
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .stat-card.critical .value { color: var(--severity-critical); }
        .stat-card.warning .value { color: var(--severity-warning); }
        .stat-card.info .value { color: var(--severity-info); }
        .stat-card.total .value { color: var(--accent-indigo); }

        /* Filter Tabs */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
            overflow-x: auto;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 0.5rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--text-primary);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .tab-btn.active {
            color: #white;
            background-color: var(--accent-indigo);
        }

        /* Findings List */
        .finding-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .finding-header {
            padding: 1.25rem;
            background-color: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .finding-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.critical { background-color: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge.warning { background-color: rgba(245, 158, 11, 0.15); color: #fde047; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge.info { background-color: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }

        .finding-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .finding-path {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .finding-content {
            padding: 1.5rem;
            display: none; /* Collapsible by default */
        }

        .finding-content.open {
            display: block;
        }

        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent-indigo);
            margin-bottom: 0.5rem;
            margin-top: 1rem;
        }

        .section-title:first-child {
            margin-top: 0;
        }

        /* Code Block Container */
        .code-comparison {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .code-box {
            background-color: #0b0f19;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .code-box-header {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .code-box.before .code-box-header {
            background-color: rgba(239, 68, 68, 0.05);
            color: #f87171;
        }

        .code-box.after .code-box-header {
            background-color: rgba(16, 185, 129, 0.05);
            color: #34d399;
        }

        pre {
            padding: 1rem;
            overflow-x: auto;
            font-family: 'Fira Code', 'Courier New', Courier, monospace;
            font-size: 0.85rem;
        }

        code {
            color: #cbd5e1;
        }

        /* Instruction text formatting */
        .step-list {
            margin-left: 1.25rem;
            margin-bottom: 1rem;
        }

        .step-list li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>Laporan Audit Kode Program</h1>
            <p>Hasil analisis komprehensif keamanan, kualitas, database, dan infrastruktur aplikasi.</p>
            <div class="meta-grid">
                <div class="meta-item">Aplikasi: <strong>[Nama Aplikasi / Repositori]</strong></div>
                <div class="meta-item">Tanggal Audit: <strong>[Tanggal Pelaksanaan]</strong></div>
                <div class="meta-item">Auditor: <strong>[Nama AI Agent / Versi]</strong></div>
            </div>
        </header>

        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card total">
                <h3>Total Temuan</h3>
                <div class="value">[Total]</div>
            </div>
            <div class="stat-card critical">
                <h3>Kritis (Critical)</h3>
                <div class="value">[Jumlah Kritis]</div>
            </div>
            <div class="stat-card warning">
                <h3>Peringatan (Warning)</h3>
                <div class="value">[Jumlah Warning]</div>
            </div>
            <div class="stat-card info">
                <h3>Informasi (Info)</h3>
                <div class="value">[Jumlah Info]</div>
            </div>
        </div>

        <!-- Tabs Filter -->
        <div class="tabs">
            <button class="tab-btn active" onclick="filterCategory('all')">Semua Temuan</button>
            <button class="tab-btn" onclick="filterCategory('security')">Keamanan</button>
            <button class="tab-btn" onclick="filterCategory('quality')">Struktur & Kualitas</button>
            <button class="tab-btn" onclick="filterCategory('database')">Database</button>
            <button class="tab-btn" onclick="filterCategory('infrastructure')">Infrastruktur</button>
        </div>

        <!-- List of Findings -->
        <div id="findings-list">
            
            <!-- CONTOH KARTU TEMUAN (AI Agent akan me-looping template ini untuk setiap temuan) -->
            <div class="finding-card" data-category="security">
                <div class="finding-header" onclick="toggleDetails(this)">
                    <div class="finding-header-left">
                        <span class="badge critical">Critical</span>
                        <div>
                            <div class="finding-title">[Nama Masalah, contoh: Potensi SQL Injection]</div>
                            <div class="finding-path">[Lokasi File, contoh: src/controllers/userController.js:14]</div>
                        </div>
                    </div>
                    <div>
                        <span class="toggle-icon">▼</span>
                    </div>
                </div>
                <div class="finding-content">
                    <div class="section-title">Deskripsi & Dampak</div>
                    <p>[Deskripsikan kerentanan secara detail, mengapa ini berbahaya, dan dampak yang ditimbulkan pada aplikasi/sistem].</p>
                    
                    <div class="section-title">Rekomendasi Tata Cara Perbaikan</div>
                    <ol class="step-list">
                        <li>[Langkah 1: Tindakan yang harus diambil]</li>
                        <li>[Langkah 2: Konfigurasi/fungsi yang harus diimplementasikan]</li>
                    </ol>

                    <div class="section-title">Rekomendasi Perubahan Struktur / Kode</div>
                    <div class="code-comparison">
                        <!-- Kode Sebelum -->
                        <div class="code-box before">
                            <div class="code-box-header">Kode / Konfigurasi Saat Ini</div>
                            <pre><code>[Kode Bermasalah]</code></pre>
                        </div>
                        <!-- Kode Rekomendasi -->
                        <div class="code-box after">
                            <div class="code-box-header">Rekomendasi Perbaikan</div>
                            <pre><code>[Kode Rekomendasi Hasil Perbaikan]</code></pre>
                        </div>
                    </div>
                </div>
            </div>
            <!-- SELESAI CONTOH KARTU TEMUAN -->

        </div>
    </div>

    <script>
        // Fungsi untuk buka-tutup detail kartu temuan
        function toggleDetails(headerElement) {
            const content = headerElement.nextElementSibling;
            const icon = headerElement.querySelector('.toggle-icon');
            
            content.classList.toggle('open');
            if (content.classList.contains('open')) {
                icon.textContent = '▲';
            } else {
                icon.textContent = '▼';
            }
        }

        // Fungsi untuk memfilter berdasarkan kategori
        function filterCategory(category) {
            // Update active tab button styling
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter cards
            const cards = document.querySelectorAll('.finding-card');
            cards.forEach(card => {
                if (category === 'all') {
                    card.style.display = 'block';
                } else if (card.getAttribute('data-category') === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
```

---

## 6. PRINSIP FOKUS & JAMINAN KUALITAS (FOCUS & QUALITY ASSURANCE)

Untuk memastikan laporan audit yang dihasilkan tetap berkualitas tinggi, terarah, dan bebas dari informasi sampah (*noise*), AI Agent wajib mengikuti aturan jaminan kualitas berikut:

### A. Prinsip Pengurangan Derau (Noise Reduction)
*   **Hindari Akumulasi Masalah Minor Kosmetik:** Dilarang keras membuat kartu temuan terpisah untuk masalah formatting minor (seperti kesalahan spasi, inden, tanda kurung, linting dasar, atau penulisan dokumentasi komentar yang kurang).
*   **Konsolidasi Temuan Minor:** Kumpulkan semua temuan kualitas kode kosmetik yang bersifat non-kritis ke dalam **satu entri temuan tunggal** dengan kategori `quality` bernilai `Info/Minor` dengan nama *"Saran Perapian Basis Kode (Code Cleanliness & Formatting Suggestions)"*. Ini bertujuan agar laporan utama tidak dipenuhi oleh ratusan temuan kosmetik yang mengaburkan isu keamanan.

### B. Validasi Kode & Rekomendasi (No-Hallucination Guardrails)
*   **Hanya Gunakan Fitur Bawaan/Resmi:** Rekomendasi perbaikan kode harus menggunakan library, pustaka, atau modul bawaan bahasa pemrograman/framework terkait yang sudah terbukti aman. Dilarang merekomendasikan instalasi library pihak ketiga baru yang tidak dikenal atau fiktif (*hallucinated packages*).
*   **Kejelasan Sebelum vs Sesudah:** Pastikan blok perbandingan kode (`before` dan `after`) berfokus langsung pada baris kode yang diubah. Kode pengganti harus dapat langsung disalin-tempel dan diintegrasikan oleh developer manusia tanpa menimbulkan error sintaksis baru.

### C. Daftar Periksa Penyelesaian (Definition of Done Checklist)
Sebelum AI Agent menghasilkan berkas `laporan_audit.html` dan mengakhiri sesi analisis, AI Agent wajib memverifikasi secara mandiri hal-hal berikut:
1.  `[ ]` Apakah total temuan pada *Dashboard Cards* di bagian atas laporan sesuai secara matematis dengan jumlah kartu temuan di bagian bawah?
2.  `[ ]` Apakah seluruh teks program di dalam tag `<pre><code>` telah melalui proses escaping karakter khusus (`<` menjadi `&lt;`, `>` menjadi `&gt;`, dan `&` menjadi `&amp;`)?
3.  `[ ]` Apakah temuan kritis (*Critical* dan *Warning*) diletakkan di urutan paling atas untuk memudahkan tindakan prioritas oleh manusia?
4.  `[ ]` Apakah semua temuan merujuk pada file riil dan baris kode yang valid di dalam repositori? (Dilarang berasumsi atau memalsukan lokasi file).

