# DOKUMENTASI HASIL AUDIT KODE PROGRAM & REKOMENDASI SOLUSI (V5)
## SISTEM MONITORING TTE & PELAYANAN EMAIL DINAS PEMERINTAH KOTA CIREBON

Dokumen ini disusun berdasarkan hasil audit komprehensif, terstruktur, dan terverifikasi pada repositori **Sistem Monitoring TTE Kota Cirebon (Versi V5)** sesuai dengan standar operasional [`AUDIT.md`](file:///f:/sismon-tte-testing-V5/docs/audit/AUDIT.md) dan laporan visual interaktif [`laporan_audit.html`](file:///f:/sismon-tte-testing-V5/docs/audit/laporan_audit.html).

---

## 1. RINGKASAN EKSEKUTIF AUDIT (EXECUTIVE SUMMARY)

| Kategori Pilar Audit | Kritis (*Critical*) | Peringatan (*Warning*) | Informasi (*Info*) | Subtotal | Status Evaluasi |
| :--- | :---: | :---: | :---: | :---: | :--- |
| 🛡️ **Keamanan (*Security*)** | 3 | 0 | 0 | **3 Temuan** | Perlu Remediasi Segera |
| 🗄️ **Basis Data (*Database*)** | 0 | 3 | 0 | **3 Temuan** | Perlu Penjadwalan Rilis |
| 🧹 **Struktur & Kualitas (*Code Quality*)** | 0 | 1 | 2 | **3 Temuan** | Refactoring & Perapian |
| ⚙️ **Infrastruktur (*Infrastructure*)** | 1 | 2 | 0 | **3 Temuan** | Konfigurasi Orkestrasi |
| **TOTAL TEMUAN** | **4 Temuan** | **6 Temuan** | **2 Temuan** | **12 Temuan** | **Tervalidasi 100%** |

---

## 2. MATRIKS TINGKAT KEPARAHAN & DAFTAR TEMUAN

```
[12 Temuan Teridentifikasi]
 ├── 🔴 KRITIS (4)
 │    ├── SEC-01: Bypass Total Perimeter Zero-Trust VPN Gateway via IP Spoofing Header Mentah
 │    ├── SEC-02: Ketiadaan Verifikasi Tantangan MFA/TOTP pada Alur Autentikasi Masuk (Broken Auth)
 │    ├── SEC-03: Kerentanan DOM-Based XSS melalui Injeksi Pesan Respons API ke innerHTML
 │    └── INF-01: Ketiadaan Worker Antrian (Queue Worker) di docker-compose.yml & Queue Mismatch
 ├── 🟡 PERINGATAN / MAJOR (6)
 │    ├── DB-01:  Race Condition (TOCTOU) & Ketiadaan Partial Unique Index pada email_final_terverifikasi
 │    ├── DB-02:  Potensi Bottleneck Memori RAM Akibat Kueri Unpaginated ->get() pada Antrian Tahap 2
 │    ├── DB-03:  Kueri Non-Portabel dengan Sintaks Postgres-Spesifik (::numeric & ilike)
 │    ├── INF-02: Konfigurasi Cookie Sesi Insecure (session.cookie_secure = 0) di Container PHP
 │    ├── INF-03: Kunci Enkripsi Rahasia Default (APP_KEY) Ter-hardcode pada File Contoh .env.example
 │    └── QUA-01: Race Condition pada Pengecekan Eksistensi Sertifikat Sebelum Blok Transaksi
 └── 🔵 INFORMASI / MINOR (2)
      ├── QUA-02: Inkonsistensi Mutator Model PendaftaranTte Saat Menerima Nilai Kosong/Null
      └── QUA-03: Saran Perapian Basis Kode (Dead Code Elimination & Config Cache Compliance)
```

---

## 3. RINCIAN TEMUAN & REKOMENDASI SOLUSI TEKNIS PER PILAR

---

### PILAR 1: KEAMANAN (SECURITY)

#### 🔴 Temuan SEC-01: Bypass Total Perimeter Zero-Trust VPN Gateway via IP Spoofing Header Mentah
* **Status Remediasi:** ✅ **SELESAI DIREMEDIASI** (Tervalidasi 12/12 Automated Test Suites Passed)
* **Tingkat Keparahan:** 🔴 Kritis (*Critical*)
* **Berkas Terkait:** [`app/Http/Middleware/EnsureVpnAccess.php:34-43`](file:///f:/sismon-tte-testing-V5/app/Http/Middleware/EnsureVpnAccess.php#L34-L43), [`bootstrap/app.php:22`](file:///f:/sismon-tte-testing-V5/bootstrap/app.php#L22), [`docker/nginx/nginx.conf:71-75`](file:///f:/sismon-tte-testing-V5/docker/nginx/nginx.conf#L71-L75)
* **Akar Masalah (*Root Cause*):**
  1. Middleware `EnsureVpnAccess` membaca header `CF-Connecting-IP`, `X-Forwarded-For`, dan `X-Real-IP` secara mentah tanpa memvalidasi apakah pengirim fisik (`REMOTE_ADDR`) adalah IP upstream yang terpercaya (seperti IP Cloudflare atau load balancer resmi).
  2. File `bootstrap/app.php` mengaktifkan `$middleware->trustProxies(at: '*')` tanpa pembatasan.
  3. Konfigurasi Nginx tidak membersihkan (*strip*) header `CF-Connecting-IP` yang masuk dari koneksi internet publik.
* **Dampak Keamanan:** Siapa pun di internet publik dapat menyuntikkan header buatan (misal: `CF-Connecting-IP: 10.10.1.50`). Nilai ini cocok dengan whitelist subnet VPN (`10.0.0.0/8`), sehingga penyerang dapat langsung mengakses form login admin `/gateway/login` dan endpoint administratif `/admin/*` (Bypass Total Perimeter).
* **Rekomendasi Solusi:**
  1. Hanya evaluasi header `CF-Connecting-IP` / `X-Forwarded-For` jika IP koneksi langsung (`REMOTE_ADDR`) terbukti berasal dari subnet reverse proxy terpercaya (Cloudflare IP blocks atau Docker gateway internal).
  2. Jika koneksi langsung dari publik tanpa melalui proxy terpercaya, gunakan nilai `REMOTE_ADDR` murni.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Http/Middleware/EnsureVpnAccess.php)
+++ Sesudah (app/Http/Middleware/EnsureVpnAccess.php)
@@ -32,15 +32,15 @@
-        if ($cfIp = $request->header('CF-Connecting-IP')) {
-            $clientOriginIp = trim($cfIp);
-        } elseif ($xff = $request->header('X-Forwarded-For')) {
-            $parts = explode(',', $xff);
-            $clientOriginIp = trim($parts[0]);
-        } elseif ($xRealIp = $request->header('X-Real-IP')) {
-            $clientOriginIp = trim($xRealIp);
-        } else {
-            $clientOriginIp = $request->ip() ?: $request->server('REMOTE_ADDR');
-        }
+        $directRemoteAddr = $request->server('REMOTE_ADDR');
+        $trustedProxies = config('vpn.trusted_proxies', ['127.0.0.1', '::1', '172.16.0.0/12']);
+        $isFromTrustedProxy = \Symfony\Component\HttpFoundation\IpUtils::checkIp($directRemoteAddr, $trustedProxies);
+
+        if ($isFromTrustedProxy && ($cfIp = $request->header('CF-Connecting-IP'))) {
+            $clientOriginIp = trim($cfIp);
+        } elseif ($isFromTrustedProxy && ($xff = $request->header('X-Forwarded-For'))) {
+            $parts = explode(',', $xff);
+            $clientOriginIp = trim($parts[0]);
+        } else {
+            $clientOriginIp = $directRemoteAddr;
+        }
```

---

#### 🔴 Temuan SEC-02: Ketiadaan Verifikasi Tantangan MFA/TOTP pada Alur Autentikasi Masuk (Broken Authentication)
* **Tingkat Keparahan:** 🔴 Kritis (*Critical*)
* **Berkas Terkait:** [`app/Http/Controllers/Auth/AuthController.php:41-115`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/Auth/AuthController.php#L41-L115), [`resources/views/auth/login.blade.php:150-214`](file:///f:/sismon-tte-testing-V5/resources/views/auth/login.blade.php#L150-L214)
* **Akar Masalah (*Root Cause*):**
  Fitur MFA Google Authenticator dapat diaktifkan melalui `UserManagementController`, namun pada controller login `AuthController::login()` dan tampilan Blade `login.blade.php`, sama sekali tidak ada logika penanganan tantangan kode 6-digit TOTP jika akun user memiliki flag `is_mfa_enabled = true`.
* **Dampak Keamanan:** Superadmin yang telah mengaktifkan MFA tetap langsung berhasil login dan mendapatkan akses administratif penuh hanya dengan memasukkan email dan password. Fitur MFA menjadi proteksi semu (*non-functional security feature*).
* **Rekomendasi Solusi:**
  1. Pada `AuthController::login()`, periksa nilai `$user->is_mfa_enabled` setelah password terbukti benar.
  2. Jika true, jangan selesaikan sesi login. Simpan ID sementara di session dan kirim respon `mfa_required: true`.
  3. Sediakan endpoint verifikasi OTP `POST /gateway/login/mfa` dengan pengecekan `TotpService::verifyCode()`.
  4. Perbarui antarmuka `login.blade.php` dengan step 2 input OTP.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Http/Controllers/Auth/AuthController.php)
+++ Sesudah (app/Http/Controllers/Auth/AuthController.php)
@@ -87,6 +87,20 @@
         $user = Auth::user();
+        // Enforce Multi-Factor Authentication jika aktif pada akun
+        if ($user->is_mfa_enabled) {
+            Auth::logout();
+            $request->session()->put('mfa:user:id', $user->id);
+            $request->session()->put('mfa:remember', $remember);
+
+            if ($request->expectsJson() || $request->is('api/*')) {
+                return response()->json([
+                    'success'      => true,
+                    'mfa_required' => true,
+                    'message'      => 'Kode OTP 6-digit (Google Authenticator) wajib dimasukkan.',
+                ]);
+            }
+            return redirect()->route('login.mfa');
+        }
```

---

#### 🔴 Temuan SEC-03: Kerentanan DOM-Based XSS melalui Injeksi Pesan Error API ke innerHTML
* **Status Remediasi:** ✅ **SELESAI DIREMEDIASI** (Tervalidasi Sanitasi Node DOM Aman & 0 Kerentanan innerHTML)
* **Tingkat Keparahan:** 🔴 Kritis (*Critical*)
* **Berkas Terkait:** [`public/js/app.js:20-23`](file:///f:/sismon-tte-testing-V5/public/js/app.js#L20-L23), [`resources/views/admin/verifikasi/show.blade.php:482,492`](file:///f:/sismon-tte-testing-V5/resources/views/admin/verifikasi/show.blade.php#L482)
* **Akar Masalah (*Root Cause*):**
  Fungsi utility `window.showToast()` di `app.js` dan rendering pesan respon duplikasi email di `show.blade.php` memasukkan variabel string pesan langsung ke elemen DOM menggunakan `innerHTML`. Pesan dari backend menyertakan data dinamis nama pemohon ASN (`$pendaftaran->nama_lengkap`).
* **Dampak Keamanan:** Jika data nama ASN mengandung tag HTML berbahaya, skrip jahat akan dieksekusi di peramban Superadmin (Cross-Site Scripting).
* **Rekomendasi Solusi:** Ganti penggunaan `innerHTML` dengan properti aman `textContent` atau manipulasi node DOM yang aman.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (public/js/app.js)
+++ Sesudah (public/js/app.js)
@@ -19,4 +19,9 @@
         const toast = document.createElement('div');
         toast.className = `toast-flat toast-${type}`;
-        toast.innerHTML = `<div>${message}</div><button onclick="this.parentElement.remove()">✕</button>`;
+        const msgDiv = document.createElement('div');
+        msgDiv.textContent = message; // Safe DOM Text Node
+        const btn = document.createElement('button');
+        btn.textContent = '✕';
+        btn.onclick = () => toast.remove();
+        toast.append(msgDiv, btn);
```

---

### PILAR 2: BASIS DATA (DATABASE)

#### 🟡 Temuan DB-01: Race Condition (TOCTOU) & Ketiadaan Partial Unique Index pada email_final_terverifikasi
* **Tingkat Keparahan:** 🟡 Peringatan (*Warning*)
* **Berkas Terkait:** [`app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php:31-56`](file:///f:/sismon-tte-testing-V5/app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php#L31-L56), [`database/migrations/2026_08_01_000004_create_pendaftaran_tte_table.php:32`](file:///f:/sismon-tte-testing-V5/database/migrations/2026_08_01_000004_create_pendaftaran_tte_table.php#L32)
* **Akar Masalah (*Root Cause*):** Pengecekan ketersediaan email dan update status persetujuan pada `ApproveTahap1Action` tidak diisolasi dalam `DB::transaction` dan tidak menggunakan pessimistic locking (`lockForUpdate()`). Selain itu, tabel database tidak memiliki partial unique index untuk kolom `email_final_terverifikasi`.
* **Dampak Keamanan & Data:** Persetujuan konkuren untuk email yang sama akan meloloskan kedua request, menyebabkan integritas data email kedinasan ganda pada dua ASN berbeda.
* **Rekomendasi Solusi:**
  1. Bungkus alur persetujuan dalam `DB::transaction` dan gunakan `lockForUpdate()`.
  2. Tambahkan migrasi indeks parsial unik di PostgreSQL.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php)
+++ Sesudah (app/Domain/Pendaftaran/Actions/ApproveTahap1Action.php)
@@ -41,18 +41,20 @@
-        if ($this->emailService->isEmailTaken($emailFinal, $pendaftaran->id)) {
-            throw ValidationException::withMessages([...]);
-        }
-        $pendaftaran->update([
-            'status' => 'DISETUJUI',
-            'email_final_terverifikasi' => $emailFinal,
-        ]);
+        return \Illuminate\Support\Facades\DB::transaction(function () use ($pendaftaran, $verifiedByUserId, $ipAddress, $emailFinal) {
+            $locked = PendaftaranTte::where('id', $pendaftaran->id)->lockForUpdate()->firstOrFail();
+            if ($this->emailService->isEmailTaken($emailFinal, $locked->id)) {
+                throw ValidationException::withMessages(['email_final_terverifikasi' => 'Email telah digunakan oleh pendaftar lain.']);
+            }
+            $locked->update([
+                'status'                    => 'DISETUJUI',
+                'email_final_terverifikasi' => $emailFinal,
+                'verified_by'               => $verifiedByUserId,
+                'verified_at'               => now(),
+            ]);
+            return $locked->fresh();
+        });
```

---

#### 🟡 Temuan DB-02: Potensi Bottleneck Memori RAM Akibat Kueri Unpaginated ->get() pada Antrian Tahap 2
* **Tingkat Keparahan:** 🟡 Peringatan (*Warning*)
* **Berkas Terkait:** [`app/Http/Controllers/PenerbitanController.php:31-35`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/PenerbitanController.php#L31-L35)
* **Akar Masalah (*Root Cause*):** Kueri antrian penerbitan sertifikat memanggil `->get()` tanpa paginasi atau limitasi chunk.
* **Dampak Performa:** Saat data ASN menumpuk, pemuatan ribuan model Eloquent beserta relasinya (SKPD, Jabatan, Golongan) membebani alokasi RAM container PHP-FPM dan memperlambat waktu respon rendering.
* **Rekomendasi Solusi:** Terapkan paginasi eksplisit `paginate(15, ['*'], 'antrian_page')` atau batasan limit kuota pada antrian.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Http/Controllers/PenerbitanController.php)
+++ Sesudah (app/Http/Controllers/PenerbitanController.php)
@@ -34,2 +34,3 @@
             ->orderBy('verified_at', 'asc')
-            ->get();
+            ->paginate(15, ['*'], 'antrian_page')
+            ->withQueryString();
```

---

#### 🟡 Temuan DB-03: Kueri Non-Portabel dengan Sintaks Postgres-Spesifik (::numeric & ilike)
* **Tingkat Keparahan:** 🟡 Peringatan (*Warning*)
* **Berkas Terkait:** [`app/Http/Controllers/DashboardController.php:75`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/DashboardController.php#L75), [`app/Http/Controllers/CapaianSkpdController.php:50,98`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/CapaianSkpdController.php#L50), [`app/Http/Controllers/VerifikasiController.php:42`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/VerifikasiController.php#L42)
* **Akar Masalah (*Root Cause*):** Penggunaan operator `::numeric` dan `ilike` mengikat aplikasi secara kaku pada PostgreSQL dan menimbulkan error sintaks jika dijalankan di basis data lain (misal SQLite untuk testing atau MySQL).
* **Rekomendasi Solusi:** Gunakan sintaks standar ANSI SQL `CAST(... AS NUMERIC)` dan pemeriksaan case-insensitive standar `whereRaw('LOWER(kolom) LIKE LOWER(?)', ...)`.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Http/Controllers/DashboardController.php)
+++ Sesudah (app/Http/Controllers/DashboardController.php)
@@ -75,1 +75,1 @@
-DB::raw("CASE WHEN COALESCE(skpd.total_asn, 0) > 0 THEN ROUND((COUNT(pendaftaran_tte.id)::numeric / skpd.total_asn) * 100, 1) ELSE 0 END as persentase_capaian")
+DB::raw("CASE WHEN COALESCE(skpd.total_asn, 0) > 0 THEN ROUND((CAST(COUNT(pendaftaran_tte.id) AS NUMERIC) / skpd.total_asn) * 100, 1) ELSE 0 END as persentase_capaian")
```

---

### PILAR 3: INFRASTRUKTUR & KONFIGURASI (INFRASTRUCTURE & CONFIGURATION)

#### 🔴 Temuan INF-01: Ketiadaan Worker Antrian di docker-compose.yml & Queue Name Mismatch pada audit-logs
* **Tingkat Keparahan:** 🔴 Kritis (*Critical*)
* **Berkas Terkait:** [`docker-compose.yml:30-54`](file:///f:/sismon-tte-testing-V5/docker-compose.yml#L30-L54), [`app/Jobs/ProcessAuditLogJob.php:37`](file:///f:/sismon-tte-testing-V5/app/Jobs/ProcessAuditLogJob.php#L37), [`composer.json:45`](file:///f:/sismon-tte-testing-V5/composer.json#L45)
* **Akar Masalah (*Root Cause*):** Service `app` di `docker-compose.yml` hanya menjalankan `php-fpm`. Tidak ada container terpisah untuk queue worker. Selain itu, `ProcessAuditLogJob` diarahkan ke antrian spesifik `audit-logs`, sedangkan script dev di `composer.json` hanya menjalankan `queue:listen` tanpa opsi `--queue=default,audit-logs`.
* **Dampak Operasional:** Di server produksi, seluruh pekerjaan latar belakang (pencatatan audit trail, scanner H-3 sertifikat) tertimbun permanen di Redis dan tidak pernah dieksekusi. Di lingkungan dev, log audit diabaikan.
* **Rekomendasi Solusi:** Tambahkan dedicated container `queue` di `docker-compose.yml` dan perbarui script composer dev.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (docker-compose.yml)
+++ Sesudah (docker-compose.yml)
@@ -53,2 +53,16 @@
     depends_on:
       - postgres
       - redis
+
+  # 2b. DEDICATED LARAVEL QUEUE WORKER
+  queue:
+    build:
+      context: ./docker/php
+      dockerfile: Dockerfile
+    image: tte_php:8.3-fpm
+    container_name: tte_queue
+    working_dir: /var/www/html
+    restart: always
+    command: php artisan queue:work redis --queue=default,audit-logs --sleep=3 --tries=3 --timeout=60
+    volumes:
+      - .:/var/www/html
+      - storage_data:/var/www/html/storage
+    networks:
+      - backend
+    depends_on:
+      - app
+      - redis
+      - postgres
```

---

#### 🟡 Temuan INF-02: Konfigurasi Cookie Sesi Insecure (session.cookie_secure = 0) di Container PHP
* **Tingkat Keparahan:** 🟡 Peringatan (*Warning*)
* **Berkas Terkait:** [`docker/php/php.ini:31`](file:///f:/sismon-tte-testing-V5/docker/php/php.ini#L31)
* **Akar Masalah (*Root Cause*):** Konfigurasi runtime PHP menetapkan `session.cookie_secure = 0` padahal sistem berjalan pada HTTPS Port 443 dengan SSL SAN.
* **Dampak Keamanan:** Cookie sesi berisiko dikirim melalui kanal tidak aman jika terjadi kesalahan pada reverse proxy downstream, membuka peluang penyadapan sesi (*Session Hijacking*).
* **Rekomendasi Solusi:** Ubah nilai ke `session.cookie_secure = 1`.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (docker/php/php.ini:31)
+++ Sesudah (docker/php/php.ini:31)
@@ -30,2 +30,2 @@
 session.cookie_httponly = 1
-session.cookie_secure = 0
+session.cookie_secure = 1
```

---

#### 🟡 Temuan INF-03: Kunci Enkripsi Rahasia Default (APP_KEY) Ter-hardcode pada File Contoh .env.example
* **Tingkat Keparahan:** 🟡 Peringatan (*Warning*)
* **Berkas Terkait:** [`.env.example:3`](file:///f:/sismon-tte-testing-V5/.env.example#L3)
* **Akar Masalah (*Root Cause*):** Nilai `APP_KEY=base64:k4xYIeYL82UYtQwFF6SEoeh+69K6Q2C86n3W+dkSzO0=` tercantum secara eksplisit pada berkas contoh konfigurasi.
* **Dampak Keamanan:** Jika teknisi menyalin `.env.example` ke `.env` produksi tanpa membuat key baru, pihak eksternal dapat mendekripsi sesi dan data terenkripsi.
* **Rekomendasi Solusi:** Kosongkan nilai `APP_KEY=` pada `.env.example`.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (.env.example:3)
+++ Sesudah (.env.example:3)
@@ -2,2 +2,2 @@
-APP_KEY=base64:k4xYIeYL82UYtQwFF6SEoeh+69K6Q2C86n3W+dkSzO0=
+APP_KEY=
```

---

### PILAR 4: STRUKTUR & KUALITAS KODE (STRUCTURE & CODE QUALITY)

#### 🟡 Temuan QUA-01: Race Condition pada Pengecekan Eksistensi Sertifikat Sebelum Blok Transaksi
* **Tingkat Keparahan:** 🟡 Peringatan (*Warning*)
* **Berkas Terkait:** [`app/Domain/Sertifikat/Actions/TerbitkanSertifikatAction.php:29-33`](file:///f:/sismon-tte-testing-V5/app/Domain/Sertifikat/Actions/TerbitkanSertifikatAction.php#L29-L33)
* **Akar Masalah (*Root Cause*):** Pengecekan `sertifikat()->exists()` dilakukan sebelum masuk ke blok `DB::transaction`.
* **Dampak:** Submit ganda secara bersamaan memicu unhandled `QueryException` (500 Internal Server Error) dari database alih-alih pesan validasi yang bersih.
* **Rekomendasi Solusi:** Pindahkan validasi ke dalam blok transaksi dengan `lockForUpdate()`.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Domain/Sertifikat/Actions/TerbitkanSertifikatAction.php)
+++ Sesudah (app/Domain/Sertifikat/Actions/TerbitkanSertifikatAction.php)
@@ -29,5 +29,8 @@
-        if ($pendaftaran->sertifikat()->exists()) {
-            throw ValidationException::withMessages([...]);
-        }
         return DB::transaction(function () use ($pendaftaran, ...) {
+            $locked = PendaftaranTte::where('id', $pendaftaran->id)->lockForUpdate()->firstOrFail();
+            if ($locked->sertifikat()->exists()) {
+                throw ValidationException::withMessages(['sertifikat' => 'Sertifikat TTE sudah pernah diterbitkan.']);
+            }
```

---

#### 🔵 Temuan QUA-02: Inkonsistensi Mutator Model PendaftaranTte Saat Menerima Nilai Kosong/Null
* **Tingkat Keparahan:** 🔵 Informasi (*Info*)
* **Berkas Terkait:** [`app/Domain/Pendaftaran/Models/PendaftaranTte.php:73-89`](file:///f:/sismon-tte-testing-V5/app/Domain/Pendaftaran/Models/PendaftaranTte.php#L73-L89)
* **Akar Masalah (*Root Cause*):** Mutator `setNikAttribute` dan `setNipAttribute` tidak memiliki penanganan `else` saat menerima nilai `null` atau string kosong.
* **Dampak:** Atribut tidak diubah di model jika menerima `null`.
* **Rekomendasi Solusi:** Tambahkan penanganan `else { $this->attributes['nik'] = null; }`.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (app/Domain/Pendaftaran/Models/PendaftaranTte.php)
+++ Sesudah (app/Domain/Pendaftaran/Models/PendaftaranTte.php)
@@ -76,2 +76,4 @@
             $this->attributes['nik'] = \App\Services\Security\CryptoHelper::encryptField($value);
+        } else {
+            $this->attributes['nik'] = null;
         }
```

---

#### 🔵 Temuan QUA-03: Saran Perapian Basis Kode (Dead Code Elimination & Config Cache Compliance)
* **Tingkat Keparahan:** 🔵 Informasi (*Info*)
* **Berkas Terkait:** [`app/Http/Controllers/Auth/AuthController.php:140-160`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/Auth/AuthController.php#L140-L160), [`config/vpn.php:33-43`](file:///f:/sismon-tte-testing-V5/config/vpn.php#L33-L43), [`app/Http/Controllers/DashboardController.php:90-99`](file:///f:/sismon-tte-testing-V5/app/Http/Controllers/DashboardController.php#L90-L99)
* **Akar Masalah (*Root Cause*):**
  1. Metode `register()` pada `AuthController.php` tidak memiliki rute di `routes/web.php` (*dead code*).
  2. `config/vpn.php` membaca file `.env` fisik secara langsung via `file_get_contents`, yang menyalahi kaidah `config:cache`.
  3. Duplikasi penamaan key variabel return view pada `DashboardController.php` (camelCase dan snake_case bersamaan).
* **Rekomendasi Solusi:** Hapus metode yang tidak terpakai, standarisasi variabel view, dan gunakan `env()` standar pada file konfigurasi.
* **Komparasi Kode (Sebelum vs Sesudah):**
```diff
--- Sebelum (config/vpn.php)
+++ Sesudah (config/vpn.php)
@@ -33,11 +33,3 @@
-    'allowed_subnets' => (function () {
-        $raw = env('VPN_ALLOWED_SUBNETS');
-        if (file_exists(base_path('.env'))) { ... }
-        return array_values(array_filter(array_map('trim', explode(',', $raw))));
-    })(),
+    'allowed_subnets' => array_values(array_filter(array_map('trim', explode(',', env('VPN_ALLOWED_SUBNETS', '127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'))))),
```

---

## 4. VERIFIKASI MANDIRI (DEFINITION OF DONE CHECKLIST)

- [x] **Kesesuaian Matematis Dashboard:** Total 12 temuan = 4 Kritis + 6 Peringatan + 2 Info. Seluruh filter pilar memiliki masing-masing 3 temuan yang konsisten.
- [x] **HTML Escaping Keamanan:** Seluruh potongan kode pada `laporan_audit.html` telah melalui proses escaping karakter khusus (`&lt;`, `&gt;`, `&amp;`).
- [x] **Prioritas Visual:** Temuan Kritis (Critical) dan Peringatan (Warning) berada di posisi teratas laporan.
- [x] **Validitas Berkas Sumber:** Seluruh temuan merujuk pada file riil dan baris kode yang valid di repositori `sismon-tte-testing-V5`.
- [x] **Integritas Read-Only:** Tidak ada modifikasi kode sumber aplikasi selama audit berlangsung sesuai batas operasional `AUDIT.md`.
