---
version: 3.1.0-PROD
name: enterprise-persandian-tte-design
description: "Bahasa desain antarmuka enterprise untuk Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas (@cirebonkota.go.id), diturunkan dari token system Carbon-inspired (IBM Plex Sans, IBM Plex Mono, flat 0px radius, hairline borders). Dirancang untuk dua permukaan: Portal Publik Resilien Low-Bandwidth (offline draft auto-save, fast CAPTCHA fallback, upload lampiran) dan Panel Superadmin Terisolasi VPN Gateway (antrian verifikasi, widget penetapan & rekap email dinas, fast dashboard aggregator, badge indikator VPN, dan streaming export)."

colors:
  primary: "#0f62fe"
  on-primary: "#ffffff"
  ink: "#161616"
  ink-muted: "#525252"
  ink-subtle: "#8c8c8c"
  canvas: "#ffffff"
  surface-1: "#f4f4f4"
  surface-2: "#e0e0e0"
  inverse-canvas: "#161616"
  inverse-surface-1: "#262626"
  inverse-ink: "#ffffff"
  inverse-ink-muted: "#c6c6c6"
  hairline: "#e0e0e0"
  hairline-strong: "#161616"
  blue-60: "#0043ce"
  blue-80: "#002d9c"
  blue-hover: "#0050e6"
  semantic-success: "#24a148"
  semantic-warning: "#f1c21b"
  semantic-error: "#da1e28"
  semantic-info: "#0f62fe"
  overlay-scrim: "rgba(22, 22, 22, 0.6)"

typography:
  display-xl: { fontFamily: IBM Plex Sans, fontSize: 76px, fontWeight: 300, lineHeight: 1.17, letterSpacing: -0.5px }
  display-lg: { fontFamily: IBM Plex Sans, fontSize: 60px, fontWeight: 300, lineHeight: 1.17, letterSpacing: -0.4px }
  display-md: { fontFamily: IBM Plex Sans, fontSize: 42px, fontWeight: 300, lineHeight: 1.20, letterSpacing: 0 }
  headline:   { fontFamily: IBM Plex Sans, fontSize: 32px, fontWeight: 400, lineHeight: 1.25, letterSpacing: 0 }
  card-title: { fontFamily: IBM Plex Sans, fontSize: 24px, fontWeight: 400, lineHeight: 1.33, letterSpacing: 0 }
  subhead:    { fontFamily: IBM Plex Sans, fontSize: 20px, fontWeight: 400, lineHeight: 1.40, letterSpacing: 0 }
  body-lg:    { fontFamily: IBM Plex Sans, fontSize: 18px, fontWeight: 400, lineHeight: 1.50, letterSpacing: 0 }
  body:       { fontFamily: IBM Plex Sans, fontSize: 16px, fontWeight: 400, lineHeight: 1.50, letterSpacing: 0.16px }
  body-sm:    { fontFamily: IBM Plex Sans, fontSize: 14px, fontWeight: 400, lineHeight: 1.29, letterSpacing: 0.16px }
  body-emphasis: { fontFamily: IBM Plex Sans, fontSize: 14px, fontWeight: 600, lineHeight: 1.29, letterSpacing: 0.16px }
  caption:    { fontFamily: IBM Plex Sans, fontSize: 12px, fontWeight: 400, lineHeight: 1.33, letterSpacing: 0.32px }
  button:     { fontFamily: IBM Plex Sans, fontSize: 14px, fontWeight: 400, lineHeight: 1.29, letterSpacing: 0.16px }
  eyebrow:    { fontFamily: IBM Plex Sans, fontSize: 14px, fontWeight: 400, lineHeight: 1.29, letterSpacing: 0.16px }
  mono-data:  { fontFamily: IBM Plex Mono, fontSize: 14px, fontWeight: 400, lineHeight: 1.40, letterSpacing: 0.16px }

rounded:
  none: 0px
  xs: 2px
  sm: 4px
  md: 6px
  lg: 8px
  pill: 9999px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 96px

components:
  # ── Aksi & Tombol ──────────────────────────────────────────────────────────
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-secondary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.inverse-ink}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  button-tertiary:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px
    border: 1px solid {colors.primary}
  button-danger:
    backgroundColor: "{colors.semantic-error}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.none}"
    padding: 12px 16px

  # ── Status, Data & Monitoring ──────────────────────────────────────────────
  status-badge:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body-emphasis}"
    rounded: "{rounded.pill}"
    padding: 4px 12px 4px 10px
    borderLeft: "3px solid var(--accent)"
  vpn-status-pill:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.semantic-success}"
    typography: "{typography.caption}"
    rounded: "{rounded.pill}"
    padding: 4px 10px
    border: 1px solid {colors.semantic-success}
  masked-data:
    backgroundColor: transparent
    textColor: "{colors.ink}"
    typography: "{typography.mono-data}"
    rounded: "{rounded.none}"
    padding: 0px
  stat-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.display-md}"
    rounded: "{rounded.none}"
    padding: 24px
    border: 1px solid {colors.hairline}
  data-table-header:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 12px 16px
  data-table-row:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 12px 16px
    borderBottom: 1px solid {colors.hairline}
  pagination-control:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.caption}"
    rounded: "{rounded.none}"
    padding: 8px 12px

  # ── Form & Input Resilien ──────────────────────────────────────────────────
  text-input:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 11px 16px
  select-input:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 11px 16px
  mfa-input:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.headline}"
    rounded: "{rounded.none}"
    padding: 0px
  file-upload-zone:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 20px
    border: 1px dashed {colors.hairline-strong}
  local-captcha-widget:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.none}"
    padding: 16px
    border: 1px solid {colors.hairline}
  draft-autosave-indicator:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink-muted}"
    typography: "{typography.caption}"
    rounded: "{rounded.pill}"
    padding: 4px 10px

  # ── Komponen Interaktif Penetapan & Rekap Email Dinas ───────────────────────
  email-recap-action-bar:
    backgroundColor: "{colors.surface-1}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.none}"
    padding: 16px
    border: 1px solid {colors.hairline}
---

# DESIGN.md — Spesifikasi Desain Antarmuka Sistem

**Proyek:** Sistem Dashboard Monitoring TTE & Pelayanan Email Dinas  
**Instansi:** Dinas Komunikasi dan Informatika / Bidang Persandian  
**Domain Resmi Email Dinas:** `@cirebonkota.go.id`  
**Dokumen Terkait:** [PRD.md](file:///f:/sismon-tte-testing-V4/docs/PRD.md) v3.2.0-PROD, [ARCHITECTURE.md](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md) v3.2.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.2.0-PROD, [SECURITY.md](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md) v3.2.0-PROD, [OPERATIONAL_GUIDE.md](file:///f:/sismon-tte-testing-V4/docs/OPERATIONAL_GUIDE.md) v3.2.0-PROD, [solusi_audit.md](file:///f:/sismon-tte-testing-V4/solusi_audit.md)  
**Versi Dokumen:** 3.2.0-PROD  
**Status:** Ditetapkan sebagai Panduan Desain Final Skala Nasional (Laravel 13 + Zero-Trust VPN Gateway + Post-Audit Hardened)  

---

> **Catatan Sebelumnya:**
> Versi 3.1.0-PROD (19-23 Agustus 2026) menetapkan bahasa desain Carbon Flat 0px radius dengan indikator VPN Gateway. Versi 3.2.0-PROD (24 Agustus 2026) menyelaraskan hasil audit antarmuka dan kepatuhan data pribadi ([`AUDIT.md`](file:///f:/sismon-tte-testing-V4/AUDIT.md) & [`solusi_audit.md`](file:///f:/sismon-tte-testing-V4/solusi_audit.md)):
> 1. Pendaftaran publik: Draft auto-save dialihkan ke `sessionStorage` (`tte_registration_draft_temp`) dengan penjaminan zero PII NIK/NIP mentah di browser serta auto-clear saat form disubmit.
> 2. Penyelarasan generator usulan email dinas di frontend dengan backend regex sanitasi gelar.
> 3. Antarmuka Manajemen Akun & MFA: Penambahan form step-up re-autentikasi kata sandi saat menonaktifkan MFA.

---

## 1. Filosofi & Karakteristik Desain

Desain antarmuka sistem dibangun di atas prinsip **Carbon Flat-Square**:
1. **Ketegasan Resmi (0px Radius):** Seluruh kartu, tombol, input, dan panel menggunakan sudut tegas (*flat 0px radius*), mencerminkan otoritas legal dokumen pemerintahan dan persandian resmi. Pengecualian radius pil (`rounded.pill`) dikhususkan untuk `status-badge`, `vpn-status-pill`, dan `draft-autosave-indicator`.
2. **Kerapatan Informasi (High Data Density):** Panel Superadmin dirancang padat data untuk memproses antrian ratusan ribu data ASN tanpa scrolling berlebihan.
3. **Resiliensi Visual Daerah Terpencil:** Tidak ada gambar dekoratif berat atau dependensi font eksternal. Semua ikon menggunakan SVG inline ringan, dan font **IBM Plex Sans** serta **IBM Plex Mono** dibundle 100% lokal.
4. **Visibilitas Status Keamanan Jaringan:** Panel admin menampilkan badge status koneksi aman VPN secara eksplisit untuk menjamin operator berada di jalur perimeter terenkripsi.

---

## 2. Palet Warna & Token Semantik

| Token Warna | Nilai Hex | Peruntukan Khusus Skala Nasional |
| :--- | :--- | :--- |
| `{colors.primary}` | `#0f62fe` | Aksi utama (Kirim Form, Setujui, Rekap Email, Unduh Laporan), underline fokus input. |
| `{colors.ink}` | `#161616` | Teks utama, judul, border tombol sekunder. |
| `{colors.ink-muted}` | `#525252` | Label form, header tabel data, teks bantuan. |
| `{colors.ink-subtle}` | `#8c8c8c` | Status netral (`MENUNGGU_VERIFIKASI`), placeholder form. |
| `{colors.surface-1}` | `#f4f4f4` | Background input form, zebra row tabel, background sidebar non-aktif. |
| `{colors.surface-2}` | `#e0e0e0` | Skeleton loader shimmer, garis hairline tebal. |
| `{colors.semantic-success}` | `#24a148` | Status `DISETUJUI`, sertifikat `AKTIF`, indikator email ter-rekap, badge status koneksi VPN. |
| `{colors.semantic-warning}` | `#f1c21b` | Sertifikat `WARNING_H3` (Aksen top-border 3px pada stat card), status `PERLU_PERPANJANG`. |
| `{colors.semantic-error}` | `#da1e28` | Status `DITOLAK`, sertifikat `EXPIRED`, error validasi field. |
| `{colors.inverse-surface-1}`| `#262626` | Status sertifikat `REVOKED` (Badge slate gelap tegas). |

---

## 3. Status → Warna: Tabel Pemetaan Skala Nasional

Pemetaan definitif dari enum basis data [SCHEMA.md §6](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md#6-ringkasan-status--nilai-enum) ke komponen `status-badge`:

| Status Data | Tabel Sumber | Warna Aksen `status-badge` | Visual Tambahan & Label |
| :--- | :--- | :--- | :--- |
| `MENUNGGU_VERIFIKASI` | `pendaftaran_tte` | `{colors.ink-subtle}` (Netral) | Label: `Menunggu Verifikasi` |
| `DISETUJUI` | `pendaftaran_tte` | `{colors.semantic-success}` | Label: `Disetujui` |
| `DITOLAK` | `pendaftaran_tte` | `{colors.semantic-error}` | Label: `Ditolak` (disertai tombol lihat alasan) |
| `AKTIF` | `sertifikat_tte` | `{colors.semantic-success}` | Label: `Sertifikat Aktif` |
| `WARNING_H3` | `sertifikat_tte` | `{colors.semantic-warning}` | Label: `H-3 Kadaluarsa` (Prioritas visual tinggi) |
| `EXPIRED` | `sertifikat_tte` | `{colors.semantic-error}` | Label: `Kedaluwarsa` |
| `REVOKED` | `sertifikat_tte` | `{colors.inverse-surface-1}` | Label: `Dicabut (Revoked)` |
| `PERLU_PERPANJANG` | `sertifikat_tte` | `{colors.semantic-warning}` | Label: `Perlu Perpanjangan` (Ikon Refresh) |

---

## 4. Tipografi

| Token Tipografi | Ukuran & Bobot | Penerapan UI |
| :--- | :--- | :--- |
| `{typography.display-md}` | 42px / 300 Light | Angka metrik pada `stat-card` Dashboard Superadmin. |
| `{typography.headline}` | 32px / 400 Regular | Judul halaman admin ("Antrian Verifikasi", "Monitoring H-3"). |
| `{typography.card-title}` | 24px / 400 Regular | Judul form pendaftaran publik, judul panel verifikasi data, form login VPN. |
| `{typography.body}` | 16px / 400 Regular | Teks field input, label pilihan radio status email dinas. |
| `{typography.body-sm}` | 14px / 400 Regular | Data tabel, breadcrumb navigasi, teks notifikasi. |
| `{typography.body-emphasis}` | 14px / 600 SemiBold | Label badge status, item navigasi sidebar aktif. |
| `{typography.caption}` | 12px / 400 Regular | Timestamp audit, indikator auto-save draft, badge VPN, pagination info. |
| `{typography.mono-data}` | 14px / 400 Plex Mono | NIK/NIP ter-masking, nomor referensi internal, IP VPN, SHA-256 hash. |

---

## 5. Tata Letak (Layout Architecture)

### 5.1 Portal Publik — Single Column Resilien (Mobile-First)
```
┌─────────────────────────────────────────────────────────────┐
│  PUBLIC HEADER (56px) — Logo Instansi & Judul Layanan TTE   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│       ┌─────────────────────────────────────────────┐       │
│       │  FEATURE CARD (Max-width 640px, Centered)   │       │
│       │  - Draft Autosave Badge: "Draft tersimpan"  │       │
│       │  - Field Identitas (NIK, NIP, Nama, No HP)  │       │
│       │  - Dropdown SKPD, Jabatan, Golongan         │       │
│       │  - Radio Status Email Dinas (@cirebonkota)  │       │
│       │  - File Upload Zone (Surat Rekomendasi/SK)  │       │
│       │  - CAPTCHA Widget (reCAPTCHA / Local Math)  │       │
│       │  - Button Primary: [Kirim Pendaftaran]      │       │
│       └─────────────────────────────────────────────┘       │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  PUBLIC FOOTER — Bidang Persandian Diskominfo               │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Panel Superadmin — Terisolasi VPN Gateway
```
┌──────────────┬──────────────────────────────────────────────┐
│              │ TOP BAR ADMIN (48px)                         │
│ SIDEBAR NAV  │ [● VPN DISKOMINFO TERVERIFIKASI]  Profil | 🚪│
│ (240px)      ├──────────────────────────────────────────────┤
│              │                                              │
│ • Dashboard  │  [ Dashboard Stat Cards × 4 ]                │
│ • Antrian    │  ┌──────────┬──────────┬──────────┬────────┐ │
│ • Penerbitan │  │Total ASN │Terbit TTE│ Antrian  │  H-3!  │ │
│ • Monitor H-3│  └──────────┴──────────┴──────────┴────────┘ │
│ • Export     │                                              │
│ • SKPD       │  [ DATA TABLE / VERIFIKASI PANEL ]           │
│ • Jabatan/Gol│  - NIK/NIP Masked (Click-to-reveal)          │
│ • Akun Admin │  - Widget Review & Rekap Email Dinas         │
│              │  - Aksi Setujui / Tolak via Modal            │
│              │                                              │
│              │  [ PAGINATION & STREAMING EXPORT BAR ]       │
└──────────────┴──────────────────────────────────────────────┘
```

---

## 6. Spesifikasi Komponen Khusus Skala Nasional

### 6.1 Widget Interaktif Penetapan & Rekap Email Dinas (Superadmin)
Komponen pada layar Verifikasi Tahap 1 untuk menetapkan email dinas resmi ASN:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ PENETAPAN EMAIL DINAS RESMI (@cirebonkota.go.id)                         │
├──────────────────────────────────────────────────────────────────────────┤
│ Usulan Sistem : [ ahmad.fauzi.19850314@cirebonkota.go.id ]               │
│                 [ Tombol: Gunakan Usulan (Auto-Copy ke Input) ]          │
│                                                                          │
│ Field Input Email Definitif :                                            │
│ ┌─────────────────────────────────────────────────┬────────────────────┐ │
│ │ ahmad.fauzi@cirebonkota.go.id                   │ [ REKAP EMAIL ]    │ │
│ └─────────────────────────────────────────────────┴────────────────────┘ │
│   * Superadmin dapat mengedit nama username         ^ Tombol Simpan &    │
│     secara manual sebelum merekap                     Kunci Definitif    │
│                                                                          │
│ Status Rekap : [● EMAIL TERKONFIRMASI & TEREKAP DEFINITIF]               │
└──────────────────────────────────────────────────────────────────────────┘
```

**Aturan Interaksi Komponen:**
1. Jika pendaftar belum memiliki email (`status_memiliki_email = false`), sistem menampilkan kotak usulan email.
2. Mengklik tombol **"Gunakan Usulan"** menyalin string `email_usulan` langsung ke dalam input field di bawahnya.
3. Superadmin dapat langsung mengetik di input field untuk melakukan penyesuaian manual username email dinas.
4. Menekan tombol **"Rekap Email"** di sebelah kanan input field mengirimkan mutasi AJAX/PATCH ke backend, mengunci email definitif (`email_final_terverifikasi`), dan mengubah indikator badge menjadi *Terkonfirmasi*.

### 6.2 Widget Resiliensi Konektivitas & Local Math CAPTCHA Fallback
- **Draft Auto-save Badge (`draft-autosave-indicator`):** Tampil di sudut kanan atas kartu form pendaftaran. Menyajikan state:
  - *Tersimpan secara lokal (offline)* — Warna teks `ink-subtle`.
  - *Sinkronisasi terkirim* — Warna teks `semantic-success`.
- **Local Math CAPTCHA Widget (`local-captcha-widget`):** Tampil otomatis bila Google reCAPTCHA mengalami timeout (>3s):
```
┌─────────────────────────────────────────────────────────────┐
│ KEAMANAN AKSES (Koneksi Terbatas Mode)                      │
│ Berapakah hasil dari:  14 + 8 = [ Input Angka ] [ Refresh ] │
│ * Sesi terenkripsi HMAC-SHA256                              │
└─────────────────────────────────────────────────────────────┘
```

### 6.3 Indikator Status VPN Gateway (`vpn-status-pill`)
Tampil pada bagian header panel Superadmin dan halaman login `/gateway/login`:
```
[● VPN DISKOMINFO TERVERIFIKASI (IP: 10.10.1.50)]
```
Menandakan bahwa sesi saat ini berada di dalam perimeter terenkripsi VPN Gateway Pemerintah Kota Cirebon.

### 6.4 Zone Upload Berkas Lampiran (`file-upload-zone`)
- Mendukung *drag-and-drop* atau *browse file* untuk berkas Surat Rekomendasi/SK.
- Menampilkan ukuran file, badge MIME type terverifikasi, dan indikator validasi hash SHA-256.

### 6.5 Modal Streaming Mass Export (`streaming-export-modal`)
- Saat Superadmin mengklik tombol **"Ekspor Laporan (CSV/Excel)"**, modal menampilkan *Indeterminate Micro Shimmer Progress Bar* dan estimasi pemrosesan tanpa memblokir browser.

---

### 6.6 Antarmuka Analitik Capaian Adopsi TTE SKPD (`capaian-skpd-panel`)
- **Grid Kartu Ringkasan KPI (Executive Summary Cards):**
  Menggunakan tata letak grid 4 kolom responsif dengan aksen border hairline (`var(--border-subtle)`), tipografi angka `IBM Plex Mono` berukuran 28px bold, serta sub-label keterangan bernilai kontras tinggi.
- **Progress Bar Persentase Capaian Berkode Warna (Color-Coded Progress Bar):**
  Tinggi container bar 8px dengan radius 0px murni (*flat strict*). Pewarnaan bar mengikuti token semantik:
  - 🟢 **Sangat Tinggi ($\ge 75\%$)**: Hijau Emerald (`var(--success)`)
  - 🔵 **Tinggi ($50\% - 74.9\%$)**: Biru Cyan (`var(--primary-dark)`)
  - 🟡 **Sedang ($25\% - 49.9\%$)**: Amber Kuning (`var(--warning)`)
  - ⚪ **Rendah ($< 25\%$)**: Slate Netral (`var(--ink-muted)`)
- **Toolbar Filter & Pencarian:**
  Kontainer form fleksibel dengan input pencarian teks, dropdown filter kategori capaian, tombol submit Cari, tombol Reset, dan tombol aksi primer hijau "Ekspor CSV".
- **Tabel Responsif & Kontrol Paginasi:**
  Header tabel mendukung indikator pengurutan aktif (`↑` / `↓`), zebra striping halus pada baris, dan kontrol pagination yang mempertahankan parameter filter aktif.

## 7. Pola State UI (Loading / Success / Empty / Error)

| State UI | Komponen Visual | Perilaku & Copy Pesan |
| :--- | :--- | :--- |
| **Loading** | `skeleton-loader` | Skeleton shimmer berbentuk baris tabel atau kartu statistik; tidak ada spinner layar penuh. |
| **Success** | `toast-success` | Muncul kanan atas (auto-dismiss 5 detik): *"Email dinas berhasil direkap secara definitif."* |
| **Empty** | `empty-state` | Ikon garis datar tipis: *"Antrian verifikasi kosong — seluruh data telah diproses."* |
| **Error** | `toast-error` / inline | *"Gagal memproses data. Silakan periksa isian atau ulangi beberapa saat lagi."* (Tanpa kebocoran stack trace database). |

---

## 8. Kontrol Dokumen

| Item | Keterangan |
| :--- | :--- |
| **Versi Dokumen** | **3.1.0-PROD** |
| **Tanggal Pembaruan** | 19 Agustus 2026 |
| **Dokumen Acuan** | [PRD.md](file:///f:/sismon-tte-testing-V4/docs/PRD.md) v3.1.0-PROD, [ARCHITECTURE.md](file:///f:/sismon-tte-testing-V4/docs/ARCHITECTURE.md) v3.1.0-PROD, [SCHEMA.md](file:///f:/sismon-tte-testing-V4/docs/SCHEMA.md) v3.1.0-PROD, [SECURITY.md](file:///f:/sismon-tte-testing-V4/docs/SECURITY.md) v3.1.0-PROD |
| **Riwayat Perubahan** | • v1.0.0: Desain baseline portal daerah.<br>• v2.1.0-PROD: Penyesuaian penuh standar Skala Nasional Enterprise: Komponen widget interaktif Review, Edit & Rekap Email Dinas Superadmin (`@cirebonkota.go.id`), komponen resiliensi low-bandwidth (Draft auto-save indicator, Local Math CAPTCHA Fallback, Zone Upload Lampiran ber-checksum), penambahan badge status `REVOKED`, dan modal progress Cursor Streaming Export.<br>• **v3.1.0-PROD:** Penambahan spesifikasi desain **Indikator VPN Gateway (`vpn-status-pill`)** dan pemisahan layout pintu masuk login admin `/gateway/login`. |
| **Target Pembaca** | UI/UX Designer, Frontend Developer, Product Owner (Bidang Persandian) |
