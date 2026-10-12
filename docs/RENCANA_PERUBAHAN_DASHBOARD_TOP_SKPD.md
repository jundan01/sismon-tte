# Rencana Perubahan: Penyesuaian Metrik & Logika Top SKPD Dashboard Berbasis Persentase Populasi Pegawai

Dokumen ini merinci rencana perubahan fitur dashboard pemantauan TTE, khususnya pada bagian perankingan unit kerja (SKPD). Sistem saat ini hanya mengurutkan SKPD berdasarkan volume absolut pendaftar tanpa mempertimbangkan jumlah total pegawai (ASN) di masing-masing instansi. Perubahan ini akan menambahkan data populasi pegawai (`total_asn`) ke master data SKPD, memperbarui logika agregasi analitik, serta memperkaya visualisasi dashboard dengan indikator persentase capaian adopsi TTE.

---

## 1. Latar Belakang & Analisis Masalah

Pada implementasi saat ini di `app/Http/Controllers/DashboardController.php` (baris 59–75), daftar "Top 10 SKPD" dihasilkan melalui query:
```sql
SELECT skpd.id, skpd.nama_skpd, skpd.kode_skpd,
       COUNT(pendaftaran_tte.id) as total_pendaftar, ...
GROUP BY skpd.id, skpd.nama_skpd, skpd.kode_skpd
ORDER BY total_pendaftar DESC
LIMIT 10;
```

### Masalah yang Terjadi (*Size Bias*):
1. **Bias Ukuran Instansi:** Instansi dengan populasi besar (misal Dinas Pendidikan atau RSUD dengan 500+ pegawai) akan selalu memuncaki Top 10 meski baru 10% pegawainya yang mendaftar TTE (50 orang).
2. **Pengabaian Kepatuhan Instansi Ramping:** Instansi dengan pegawai ramping (misal Bagian Kesbangpol atau Inspektorat dengan 20 pegawai) tidak akan pernah masuk radar Top 10 pimpinan, walaupun 100% pegawainya (20 orang) telah tertib dan aktif memiliki TTE.
3. **Ketidakakuratan Penilaian Kinerja Digitalisasi:** Seperti pada kasus SKPD A (30 pegawai, 15 daftar = 50%) vs SKPD B (15 pegawai, 10 daftar = 66.7%), SKPD B secara objektivitas kinerja adopsi lebih unggul namun kalah peringkat jika diukur secara volume mentah.

---

## 2. Solusi Desain yang Diusulkan

Tampilan dashboard akan menggunakan pendekatan **Tabel Gabungan (Hybrid) Berbobot** yang menampilkan kedua indikator sekaligus, dilengkapi kontrol alih cepat (toggle):
1. **Tingkat Kepatuhan / Adopsi (%)** *(Default)*: Mengukur keberhasilan penetrasi TTE terhadap total pegawai instansi (\(\frac{\text{Pendaftar}}{\text{Total ASN}} \times 100\%\)).
2. **Volume Permohonan Masuk**: Mengukur beban kerja operasional berkas verifikasi Diskominfo.

---

## 3. Rincian Perubahan Kode Program

### A. Database Schema & Migration
* **File:** `database/migrations/2026_09_09_000001_add_total_asn_to_skpd_table.php`
* **Perubahan:**
  Menambahkan kolom `total_asn` (unsigned integer, default 0) pada tabel `skpd`.

### B. Domain Model & Seeder
* **File:** `app/Domain/Master/Models/Skpd.php`
  * Tambahkan `'total_asn'` ke `$fillable`.
  * Tambahkan cast `'total_asn' => 'integer'`.
* **File:** `database/seeders/SkpdSeeder.php`
  * Tambahkan data estimasi populasi pegawai riil pada 20 SKPD awal Kota Cirebon.

### C. Manajemen Master SKPD (Admin Panel)
* **File:** `app/Http/Controllers/MasterSkpdController.php`
  * Perbarui aturan validasi `store()` dan `update()` untuk menerima `'total_asn' => ['nullable', 'integer', 'min:0']`.
  * Simpan nilai `total_asn` saat insert atau update.
* **File:** `resources/views/admin/master/skpd/index.blade.php`
  * Tambahkan kolom "Total ASN" di tabel master SKPD.
  * Tambahkan input "Total Pegawai (ASN)" pada modal *Tambah SKPD* dan *Edit SKPD*.

### D. Dashboard Controller & Analytics Query
* **File:** `app/Http/Controllers/DashboardController.php`
  * Terima parameter query `?sort=persentase` (default) atau `?sort=volume`.
  * Hitung persentase capaian secara aman:
    ```sql
    CASE 
        WHEN COALESCE(skpd.total_asn, 0) > 0 
        THEN ROUND((COUNT(pendaftaran_tte.id)::numeric / skpd.total_asn) * 100, 1)
        ELSE 0 
    END as persentase_capaian
    ```
  * Pisahkan cache key Redis sesuai mode sorting: `dashboard_skpd_distribution_stats_persentase` dan `dashboard_skpd_distribution_stats_volume`.

### E. Tampilan Dashboard Blade
* **File:** `resources/views/admin/dashboard.blade.php`
  * Tambahkan kolom "Total ASN" dan "Capaian (%)" dengan progress bar visual berwarna:
    * Hijau: \(\ge 80\%\)
    * Kuning: \(50\% - 79\%\)
    * Merah: \(< 50\%\)
    * Abu-abu: Belum ditentukan (0)
  * Tambahkan tombol filter toggle di header widget untuk memilih mode pengurutan.

---

## 4. Rencana Pengujian & Verifikasi

1. **Migrasi Database:** Menjalankan migrasi kolom baru tanpa merusak relasi tabel yang ada.
2. **Pengujian CRUD Master SKPD:** Memastikan penambahan dan pengeditan nilai `total_asn` tersimpan dengan baik.
3. **Simulasi Kasus:**
   * Input SKPD A (Total ASN = 30, Pendaftar = 15).
   * Input SKPD B (Total ASN = 15, Pendaftar = 10).
   * Memastikan pada mode urut "Kepatuhan (%)", SKPD B (66.7%) menduduki peringkat di atas SKPD A (50.0%).
   * Memastikan pada mode urut "Volume", SKPD A (15 pemohon) berada di atas SKPD B (10 pemohon).

---

## 5. Realisasi Penuh & Pengembangan Lanjutan (V5.2.0 - Menu Analitik Seluruh SKPD & Ekspor CSV)

### A. Evaluasi Pascaimplementasi Dashboard Top 10
Setelah penyesuaian metrik berbasis persentase pada widget Top 10 dashboard selesai diimplementasikan, pimpinan dan admin memberikan masukan penting:
- **Kebutuhan Memantau Seluruh Instansi:** Batasan Top 10 menyembunyikan kondisi puluhan SKPD lainnya yang berada di peringkat menengah maupun bawah, sehingga mempersulit evaluasi komprehensif seluruh instansi di Pemerintah Kota Cirebon.
- **Kebutuhan Data Pelaporan:** Diperlukan media ekspor data resmi ke format spreadsheet (CSV) yang memuat persentase capaian per SKPD untuk bahan laporan pimpinan daerah.

### B. Solusi Pengembangan Lanjutan yang Diterapkan
1. **Menu Baru: Capaian Adopsi SKPD (`/admin/capaian-skpd`):**
   - Menghadirkan halaman analitik tersendiri dengan daftar seluruh SKPD aktif tanpa batasan Top 10.
   - Dilengkapi 4 kartu KPI agregat kota, tabel interaktif dengan progress bar warna, filter pencarian nama/singkatan, filter kategori capaian, dan pengurutan fleksibel.
   - Ditambahkan tombol pintas "Lihat Seluruh SKPD & Ekspor" pada widget Top 10 dashboard untuk transisi navigasi yang mulus.
2. **Ekspor CSV Khusus Capaian Adopsi (`/admin/capaian-skpd/export`):**
   - Mengalirkan file CSV dengan standar UTF-8 BOM, nama file bertanggal otomatis, dan sinkronisasi filter aktif.
3. **Pengujian Otomatis:**
   - Ditambahkan 12 test case pada `tests/Feature/CapaianSkpdTest.php` yang memverifikasi keandalan modul analitik dan ekspor (seluruh 70 test suite aplikasi 100% PASS).
