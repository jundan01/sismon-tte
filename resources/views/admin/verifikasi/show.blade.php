@extends('layouts.admin')

@section('title', 'Detail Verifikasi Data — ' . $pendaftaran->nomor_referensi)
@section('breadcrumb')
    <a href="{{ route('admin.verifikasi.index') }}">Verifikasi Tahap 1</a>
    <span>/</span>
    <span class="font-mono">{{ $pendaftaran->nomor_referensi }}</span>
@endsection

@section('content')
<div class="panel-header">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline btn-sm">← Kembali</a>
        <div>
            <h1 class="panel-title font-mono">{{ $pendaftaran->nomor_referensi }}</h1>
            <div style="font-size: 0.75rem; color: var(--ink-muted); margin-top: 2px;">
                Permohonan didaftarkan pada {{ $pendaftaran->created_at?->format('d F Y, H:i') }} WIB
            </div>
        </div>
    </div>

    <div>
        @if($pendaftaran->status === 'MENUNGGU_VERIFIKASI')
            <span class="badge badge-menunggu" style="padding: 4px 10px; font-size: 0.75rem;">MENUNGGU VERIFIKASI</span>
        @elseif($pendaftaran->status === 'DISETUJUI')
            <span class="badge badge-disetujui" style="padding: 4px 10px; font-size: 0.75rem;">TELAH DISETUJUI</span>
        @elseif($pendaftaran->status === 'DITOLAK')
            <span class="badge badge-ditolak" style="padding: 4px 10px; font-size: 0.75rem;">DITOLAK</span>
        @endif
    </div>
</div>

<!-- ── LAYOUT DETAIL VERIFIKASI PEMOHON ASN ──────────────────────── -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(440px, 1fr)); gap: 1.5rem; align-items: start;">
    
    <!-- 1. Identitas Lengkap ASN -->
    <div class="card-flat">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--hairline); padding-bottom: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary);">
                Data Identitas Pegawai (ASN)
            </div>
            @if($pendaftaran->status === 'MENUNGGU_VERIFIKASI')
                <button type="button" class="btn btn-outline btn-sm" onclick="openModal('modal-edit-jabatan-golongan')" style="font-size: 0.75rem;">
                    ✏️ Edit Jabatan & Golongan
                </button>
            @endif
        </div>

        <table style="width: 100%; font-size: 0.8125rem; border-collapse: collapse;">
            <tr>
                <td style="width: 140px; padding: 7px 0; color: var(--ink-secondary);">Nama Lengkap</td>
                <td style="padding: 7px 0; font-weight: 700; font-size: 0.9375rem;">{{ $pendaftaran->nama_lengkap }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">NIK (KTP)</td>
                <td style="padding: 7px 0; font-weight: 600;" class="font-mono">{{ $pendaftaran->nik }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">NIP</td>
                <td style="padding: 7px 0; font-weight: 600;" class="font-mono">{{ $pendaftaran->nip }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">No. WhatsApp</td>
                <td style="padding: 7px 0;" class="font-mono">
                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', $pendaftaran->no_hp) }}" target="_blank" style="font-weight: 600; color: var(--primary);">
                        {{ $pendaftaran->no_hp }} ↗
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">Unit Kerja (SKPD)</td>
                <td style="padding: 7px 0; font-weight: 600;">{{ $pendaftaran->skpd->nama_skpd ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">Deskripsi Penempatan</td>
                <td style="padding: 7px 0; font-weight: 600;">
                    @if(!empty($pendaftaran->deskripsi))
                        <span style="background: rgba(15, 98, 254, 0.08); border: 1px solid rgba(15, 98, 254, 0.2); color: var(--primary); padding: 3px 8px; border-radius: 4px; font-size: 0.8125rem; display: inline-block;">
                            📍 {{ $pendaftaran->deskripsi }}
                        </span>
                    @else
                        <span style="color: var(--ink-muted); font-size: 0.8125rem; font-style: italic;">(Tidak ada informasi tambahan penempatan)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">Jabatan</td>
                <td style="padding: 7px 0;" id="display-jabatan-nama">
                    {{ $pendaftaran->jabatan->nama_label ?? '-' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 7px 0; color: var(--ink-secondary);">Golongan</td>
                <td style="padding: 7px 0;" id="display-golongan-nama">
                    @if($pendaftaran->golongan)
                        {{ $pendaftaran->golongan->nama_label }} ({{ $pendaftaran->golongan->kode }})
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- 2. Rekapitulasi & Penetapan Email Dinas Resmi -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="card-flat" style="border-top: 4px solid var(--primary);">
            <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary); border-bottom: 1px solid var(--hairline); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                Rekapitulasi & Penetapan Email Dinas Resmi (@cirebonkota.go.id)
            </div>

            <!-- Informasi Status Pengajuan ASN -->
            @if($pendaftaran->status_memiliki_email)
                <div style="background: #eef9fd; border: 1px solid #72c3e8; border-left: 4px solid #0072c3; padding: 0.875rem 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span class="badge" style="background: #0072c3; color: #fff; font-weight: 600; font-size: 0.6875rem;">SUDAH MEMILIKI EMAIL</span>
                            <span style="font-size: 0.8125rem; color: #0043ce; font-weight: 600; margin-left: 6px;">Email Existing dari Pemohon ASN</span>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.75rem; background: #fff;" onclick="useEmail('{{ $pendaftaran->email_existing }}')">
                            ↙ Salin ke Input Definitif
                        </button>
                    </div>
                    <div class="font-mono" style="font-size: 0.9375rem; font-weight: 700; color: #002d9c; margin-top: 6px;">
                        {{ $pendaftaran->email_existing }}
                    </div>
                    <div style="font-size: 0.75rem; color: #525252; margin-top: 4px;">
                        ℹ️ <em>Pemohon ASN menyatakan sudah memiliki email dinas aktif di atas. Admin berwenang memvalidasi dan tetap dapat mengedit / mengubah alamat email definitif di bawah ini.</em>
                    </div>
                </div>
            @else
                <div style="background: var(--surface-1); border: 1px solid var(--hairline); border-left: 4px solid var(--primary); padding: 0.875rem 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span class="badge badge-warning-h3" style="font-size: 0.6875rem;">BELUM MEMILIKI EMAIL</span>
                            <span style="font-size: 0.8125rem; color: var(--ink-secondary); font-weight: 600; margin-left: 6px;">Usulan Otomatis Sistem</span>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.75rem;" onclick="useEmail('{{ $pendaftaran->email_usulan }}')">
                            ↙ Salin ke Input Definitif
                        </button>
                    </div>
                    <div class="font-mono" id="label-usulan-email" style="font-size: 0.9375rem; font-weight: 700; color: var(--primary); margin-top: 6px;">
                        {{ $pendaftaran->email_usulan }}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--ink-muted); margin-top: 4px;">
                        ℹ️ <em>Format usulan otomatis sistem: [nama-depan].[nama-belakang].[nip]@cirebonkota.go.id. Admin dapat menggunakan usulan ini atau mengeditnya.</em>
                    </div>
                </div>
            @endif

            <!-- Form Input Penetapan Email Definitif (Admin Selalu Bisa Edit Nama/Prefix, Domain Terkunci Permanen) -->
            @php
                $defaultEmail = $pendaftaran->email_final_terverifikasi ?? ($pendaftaran->status_memiliki_email ? $pendaftaran->email_existing : $pendaftaran->email_usulan);
                $defaultPrefix = $defaultEmail ? (str_contains($defaultEmail, '@') ? explode('@', $defaultEmail)[0] : $defaultEmail) : '';
            @endphp
            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label" for="email_prefix_input">
                    Nama / Username Email Definitif Terverifikasi <span style="color: var(--danger);">*</span>
                </label>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: stretch;">
                    <!-- Input Group: Prefix Nama (Bisa Diedit) + Domain Terkunci (@cirebonkota.go.id) -->
                    <div style="display: flex; flex: 1; min-width: 300px; align-items: stretch;">
                        <input type="text" id="email_prefix_input" class="form-control font-mono"
                            style="flex: 1; border-right: none;"
                            value="{{ $defaultPrefix }}"
                            placeholder="nama.nip"
                            autocomplete="off"
                            spellcheck="false"
                            oninput="handleEmailPrefixInput(this)"
                            {{ $pendaftaran->status !== 'MENUNGGU_VERIFIKASI' ? 'disabled' : '' }}>
                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 0 0.875rem; background: var(--surface-2); border: 1px solid var(--hairline); border-left: 1px solid var(--hairline); border-bottom: 2px solid var(--surface-3); color: var(--ink-secondary); font-family: 'IBM Plex Mono', Consolas, monospace; font-size: 0.875rem; font-weight: 700; white-space: nowrap; user-select: none;">
                            <span>@cirebonkota.go.id</span>
                            <span title="Domain resmi @cirebonkota.go.id dikunci permanen untuk meminimalisir kesalahan input domain" style="font-size: 0.8125rem; cursor: help;">🔒</span>
                        </div>
                    </div>

                    <!-- Hidden input to maintain #email_final_input for script reference -->
                    <input type="hidden" id="email_final_input" value="{{ $defaultPrefix ? ($defaultPrefix . '@cirebonkota.go.id') : '' }}">

                    @if($pendaftaran->status === 'MENUNGGU_VERIFIKASI')
                        <button type="button" class="btn btn-outline" id="btn-check-collision" onclick="checkCollisionAjax()" title="Periksa apakah email sudah terdaftar atas nama ASN lain di basis data">
                            🔍 Cek Unisitas
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-rekap-email" onclick="rekapEmailAjax()">
                            Kunci & Rekap
                        </button>
                    @endif
                </div>
                <div class="form-helper" style="margin-top: 6px;">
                    Domain <strong class="font-mono" style="color: var(--primary);">@cirebonkota.go.id</strong> dikunci permanen. Superadmin hanya dapat mengedit nama/username di depan tanda @.
                </div>
            </div>

            <!-- Quick Collision Check Result Container -->
            <div id="collision-result-box" style="display: none; margin-bottom: 1rem;"></div>

            <!-- Status Indikator Rekapitulasi -->
            <div id="rekap-confirmed-badge" style="display: {{ $pendaftaran->is_email_recap_confirmed ? 'block' : 'none' }};">
                <div class="alert-flat alert-success" style="margin-bottom: 0;">
                    ✓ <strong>Email resmi telah direkapitulasi:</strong>
                    <span class="font-mono" id="rekap-confirmed-email" style="font-weight: 700;">{{ $pendaftaran->email_final_terverifikasi }}</span>
                    <div style="font-size: 0.75rem; color: var(--ink-secondary); margin-top: 2px;">
                        Admin tetap dapat mengubah / mengedit kembali alamat ini sebelum melakukan persetujuan Tahap 1.
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Panel Keputusan & Aksi Verifikator -->
        @if($pendaftaran->status === 'MENUNGGU_VERIFIKASI')
            <div class="card-flat" style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-danger" onclick="openModal('modal-reject')">
                    ✕ Tolak Permohonan
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="openModal('modal-approve')">
                    ✓ Setujui Permohonan (Tahap 1)
                </button>
            </div>
        @elseif($pendaftaran->status === 'DISETUJUI')
            <div class="card-flat" style="background: var(--success-surface); border-color: var(--success);">
                <div style="font-weight: 700; color: var(--success-text);">Permohonan Telah Disetujui</div>
                <div style="font-size: 0.75rem; color: var(--ink-secondary); margin-top: 2px;">
                    Diverifikasi oleh: <strong>{{ $pendaftaran->verifikator->name ?? 'Superadmin' }}</strong> pada {{ $pendaftaran->verified_at?->format('d/m/Y H:i') }} WIB.
                </div>
            </div>
        @elseif($pendaftaran->status === 'DITOLAK')
            <div class="card-flat" style="background: var(--danger-surface); border-color: var(--danger);">
                <div style="font-weight: 700; color: var(--danger-text);">Permohonan Ditolak</div>
                <div style="font-size: 0.8125rem; color: var(--ink); margin-top: 4px;">
                    <strong>Alasan Penolakan:</strong> {{ $pendaftaran->catatan_penolakan }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- ── MODAL KONFIRMASI PERSETUJUAN (TAHAP 1) ──────────────────── -->
<div class="modal-backdrop" id="modal-approve">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Konfirmasi Persetujuan Verifikasi (Tahap 1)</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-approve')">✕</button>
        </div>
        <form action="{{ route('admin.verifikasi.approve', $pendaftaran->nomor_referensi) }}" method="POST">
            @csrf
            <div class="modal-body">
                <p style="font-size: 0.875rem; color: var(--ink); margin-bottom: 1rem;">
                    Apakah Anda yakin ingin menyetujui permohonan pendaftaran TTE untuk ASN <strong>{{ $pendaftaran->nama_lengkap }}</strong>?
                </p>
                <div class="alert-flat alert-success">
                    Setelah disetujui, berkas ASN ini akan diteruskan ke antrian <strong>Penerbitan Sertifikat TTE Tahap 2</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-approve')">Batal</button>
                <button type="submit" class="btn btn-primary">Ya, Setujui Permohonan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL PENOLAKAN PERMOHONAN ──────────────────────────────── -->
<div class="modal-backdrop" id="modal-reject">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Formulir Penolakan Berkas Permohonan TTE</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-reject')">✕</button>
        </div>
        <form action="{{ route('admin.verifikasi.reject', $pendaftaran->nomor_referensi) }}" method="POST">
            @csrf
            <div class="modal-body">
                <p style="font-size: 0.8125rem; color: var(--ink-secondary); margin-bottom: 1rem;">
                    Harap cantumkan alasan penolakan secara jelas dan spesifik agar pemohon ASN dapat memperbaiki data persyaratan.
                </p>

                <div class="form-group">
                    <label class="form-label" for="catatan_penolakan">Catatan / Alasan Penolakan <span style="color: var(--danger);">*</span></label>
                    <textarea name="catatan_penolakan" id="catatan_penolakan" rows="4" class="form-control" placeholder="Contoh: Format nama atau NIP tidak sesuai data kepegawaian resmi BKPSDM." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-reject')">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Permohonan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL EDIT JABATAN & GOLONGAN ───────────────────────────── -->
<div class="modal-backdrop" id="modal-edit-jabatan-golongan">
    <div class="modal-flat" style="max-width: 540px;">
        <div class="modal-header">
            <h3>Koreksi / Ubah Jabatan & Golongan ASN</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-edit-jabatan-golongan')">✕</button>
        </div>
        <form id="form-edit-jabatan-golongan" action="{{ route('admin.verifikasi.update-jabatan-golongan', $pendaftaran->nomor_referensi) }}" method="POST" onsubmit="handleUpdateJabatanGolongan(event)">
            @csrf
            <div class="modal-body">
                <p style="font-size: 0.8125rem; color: var(--ink-secondary); margin-bottom: 1.25rem;">
                    Superadmin berwenang mengubah atau mengosongkan (menghapus) data Jabatan dan Golongan ASN pendaftar jika terdapat ketidaksesuaian data kepegawaian.
                </p>

                <!-- Jabatan Selection -->
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <label class="form-label" for="select_jabatan_id" style="margin-bottom: 0;">
                            Jabatan ASN
                        </label>
                        <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.6875rem; padding: 2px 6px; border-color: var(--hairline);" onclick="clearSelect('select_jabatan_id')">
                            ✕ Kosongkan Jabatan
                        </button>
                    </div>
                    <select name="jabatan_id" id="select_jabatan_id" class="form-select">
                        <option value="">-- Tidak Ada / Kosongkan Jabatan --</option>
                        @foreach($jabatanList ?? [] as $jab)
                            <option value="{{ $jab->id }}" {{ $pendaftaran->jabatan_id == $jab->id ? 'selected' : '' }}>
                                {{ $jab->nama_label }} ({{ $jab->kode }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-helper">Pilih jabatan dari master data, atau kosongkan jika tidak memiliki jabatan struktural/tertentu.</div>
                </div>

                <!-- Golongan Selection -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <label class="form-label" for="select_golongan_id" style="margin-bottom: 0;">
                            Golongan / Pangkat ASN
                        </label>
                        <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.6875rem; padding: 2px 6px; border-color: var(--hairline);" onclick="clearSelect('select_golongan_id')">
                            ✕ Kosongkan Golongan
                        </button>
                    </div>
                    <select name="golongan_id" id="select_golongan_id" class="form-select">
                        <option value="">-- Tidak Ada / Kosongkan Golongan --</option>
                        @foreach($golonganList ?? [] as $gol)
                            <option value="{{ $gol->id }}" {{ $pendaftaran->golongan_id == $gol->id ? 'selected' : '' }}>
                                {{ $gol->kode }} — {{ $gol->nama_label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-helper">Pilih golongan/ruang kepangkatan ASN yang sesuai, atau kosongkan.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit-jabatan-golongan')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-save-jabatan-golongan">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function clearSelect(selectId) {
        const el = document.getElementById(selectId);
        if (el) el.value = '';
    }

    function handleUpdateJabatanGolongan(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-save-jabatan-golongan');
        const jabatanId = document.getElementById('select_jabatan_id').value;
        const golonganId = document.getElementById('select_golongan_id').value;

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';
        }

        fetch('{{ route("admin.verifikasi.update-jabatan-golongan", $pendaftaran->nomor_referensi) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                jabatan_id: jabatanId ? parseInt(jabatanId) : null,
                golongan_id: golonganId ? parseInt(golonganId) : null
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const displayJabatan = document.getElementById('display-jabatan-nama');
                const displayGolongan = document.getElementById('display-golongan-nama');
                if (displayJabatan) displayJabatan.textContent = data.jabatan_nama;
                if (displayGolongan) displayGolongan.textContent = data.golongan_nama;

                closeModal('modal-edit-jabatan-golongan');
                window.showToast(data.message || 'Jabatan dan Golongan ASN berhasil diperbarui.', 'success');
            } else {
                let errMsg = data.message || 'Gagal memperbarui jabatan dan golongan.';
                if (data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && data.errors[firstKey][0]) {
                        errMsg = data.errors[firstKey][0];
                    }
                }
                window.showToast(errMsg, 'danger');
            }
        })
        .catch(err => {
            console.error('Error update jabatan golongan:', err);
            window.showToast('Terjadi kesalahan jaringan saat memperbarui jabatan dan golongan.', 'danger');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Simpan Perubahan';
            }
        });
    }

    function handleEmailPrefixInput(input) {
        let val = input.value.trim();
        // Jika superadmin tidak sengaja mengetik/paste dengan @, otomatis ambil nama di depannya saja
        if (val.includes('@')) {
            val = val.split('@')[0].trim();
            input.value = val;
        }
        const finalInput = document.getElementById('email_final_input');
        if (finalInput) {
            finalInput.value = val ? (val + '@cirebonkota.go.id') : '';
        }
    }

    function getFullEmail() {
        const prefixInput = document.getElementById('email_prefix_input');
        let prefix = prefixInput ? prefixInput.value.trim() : '';
        if (prefix.includes('@')) {
            prefix = prefix.split('@')[0].trim();
            if (prefixInput) prefixInput.value = prefix;
        }
        return prefix ? (prefix + '@cirebonkota.go.id') : '';
    }

    function useEmail(email) {
        if (!email) return;
        let prefix = email.trim();
        if (prefix.includes('@')) {
            prefix = prefix.split('@')[0].trim();
        }
        const prefixInput = document.getElementById('email_prefix_input');
        if (prefixInput) {
            prefixInput.value = prefix;
            prefixInput.focus();
        }
        const finalInput = document.getElementById('email_final_input');
        if (finalInput) {
            finalInput.value = prefix ? (prefix + '@cirebonkota.go.id') : '';
        }
        checkCollisionAjax();
    }

    function checkCollisionAjax() {
        const prefixInput = document.getElementById('email_prefix_input');
        const prefix = prefixInput ? prefixInput.value.trim() : '';
        const email = getFullEmail();
        const box = document.getElementById('collision-result-box');
        const btn = document.getElementById('btn-check-collision');

        if (!prefix || !email) {
            box.style.display = 'block';
            box.replaceChildren();
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-flat alert-danger';
            alertDiv.style.marginBottom = '0';
            alertDiv.textContent = '⚠ Masukkan nama/username email yang ingin dicek.';
            box.appendChild(alertDiv);
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Memeriksa...';
        }

        fetch('{{ route("admin.verifikasi.check-collision", $pendaftaran->nomor_referensi) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(res => res.json())
        .then(res => {
            box.style.display = 'block';
            box.replaceChildren();

            if (res.success && res.data) {
                if (res.data.is_available) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert-flat alert-success';
                    alertDiv.style.cssText = 'margin-bottom: 0; display: flex; align-items: center; gap: 0.5rem;';

                    const icon = document.createElement('span');
                    icon.style.fontSize = '1.125rem';
                    icon.textContent = '✓';

                    const content = document.createElement('div');
                    const strong = document.createElement('strong');
                    strong.textContent = 'Unisitas Terverifikasi: ';
                    content.appendChild(strong);
                    content.appendChild(document.createTextNode(res.data.message || 'Alamat email tersedia.'));

                    alertDiv.appendChild(icon);
                    alertDiv.appendChild(content);
                    box.appendChild(alertDiv);
                } else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert-flat alert-danger';
                    alertDiv.style.marginBottom = '0';

                    const header = document.createElement('div');
                    header.style.cssText = 'font-weight: 700; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 4px;';
                    const icon = document.createElement('span');
                    icon.textContent = '⚠';
                    header.appendChild(icon);
                    header.appendChild(document.createTextNode(' Duplikasi Terdeteksi!'));

                    const desc = document.createElement('div');
                    desc.style.fontSize = '0.8125rem';
                    desc.textContent = res.data.message || 'Email sudah digunakan.';

                    alertDiv.appendChild(header);
                    alertDiv.appendChild(desc);
                    box.appendChild(alertDiv);
                }
            } else {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert-flat alert-danger';
                alertDiv.style.marginBottom = '0';
                alertDiv.textContent = '⚠ ' + (res.message || 'Gagal memeriksa tabrakan email.');
                box.appendChild(alertDiv);
            }
        })
        .catch(err => {
            console.error('Collision check error:', err);
            box.style.display = 'block';
            box.replaceChildren();
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-flat alert-danger';
            alertDiv.style.marginBottom = '0';
            alertDiv.textContent = '⚠ Gagal menghubungi server saat pengecekan unisitas.';
            box.appendChild(alertDiv);
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = '🔍 Cek Unisitas';
            }
        });
    }

    function rekapEmailAjax() {
        const prefixInput = document.getElementById('email_prefix_input');
        const prefix = prefixInput ? prefixInput.value.trim() : '';
        if (!prefix) {
            window.showToast('Nama/username email dinas wajib diisi.', 'danger');
            if (prefixInput) prefixInput.focus();
            return;
        }

        const email = getFullEmail();

        const btn = document.getElementById('btn-rekap-email');
        btn.disabled = true;
        btn.textContent = 'Merekap...';

        fetch('{{ route("admin.verifikasi.rekap-email", $pendaftaran->nomor_referensi) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email_final_terverifikasi: email })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const confirmedEmailElem = document.getElementById('rekap-confirmed-email');
                if (confirmedEmailElem) confirmedEmailElem.textContent = data.email_final_terverifikasi;
                document.getElementById('rekap-confirmed-badge').style.display = 'block';
                checkCollisionAjax();
                window.showToast(data.message, 'success');
            } else {
                window.showToast(data.message || (data.errors && data.errors.email_final_terverifikasi ? data.errors.email_final_terverifikasi[0] : 'Gagal merekap email dinas.'), 'danger');
            }
        })
        .catch(err => {
            console.error('Gagal rekap email:', err);
            window.showToast('Terjadi kesalahan jaringan saat merekap email.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Kunci & Rekap';
        });
    }

    // Auto-run check collision on page load for convenience
    document.addEventListener('DOMContentLoaded', function() {
        const emailVal = getFullEmail();
        if (emailVal) {
            checkCollisionAjax();
        }
    });
</script>
@endpush
