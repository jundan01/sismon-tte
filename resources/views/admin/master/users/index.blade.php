@extends('layouts.admin')

@section('title', 'Manajemen Akun Superadmin & TOTP MFA')
@section('breadcrumb', 'Akun Superadmin & IAM')

@section('content')
<div class="panel-header">
    <div>
        <h1 class="panel-title">Manajemen Akun Internal & Keamanan IAM</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Pengelolaan hak akses Superadmin Bidang Persandian DKIS Kota Cirebon dan aktivasi Multi-Factor Authentication (TOTP).
        </p>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn btn-outline btn-sm" onclick="openModalMfaSetup()">
            🛡️ Setup TOTP MFA Saya
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal-add-user')">
            + Tambah Superadmin
        </button>
    </div>
</div>

<!-- ── TABEL AKUN SUPERADMIN ─────────────────────────────────── -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama Superadmin</th>
                <th>Alamat Email Resmi</th>
                <th>Peran (Role)</th>
                <th>Status MFA (TOTP)</th>
                <th>Login Terakhir</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td style="font-weight: 600;">{{ $user->name }}</td>
                    <td class="font-mono" style="font-size: 0.8125rem; color: var(--primary);">{{ $user->email }}</td>
                    <td>
                        <span class="badge" style="background:#161616; color:#fff;">SUPERADMIN</span>
                    </td>
                    <td>
                        @if($user->is_mfa_enabled)
                            <span class="badge badge-aktif">🛡️ MFA Aktif</span>
                        @else
                            <span class="badge badge-warning-h3">MFA Nonaktif</span>
                        @endif
                    </td>
                    <td class="font-mono" style="font-size: 0.75rem;">
                        {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Belum Pernah' }}
                    </td>
                    <td style="text-align: right;">
                        @if($user->is_mfa_enabled)
                            <button type="button" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--hairline);" onclick="disableMfaUser('{{ $user->uuid ?? $user->id }}', '{{ $user->email }}')">
                                Reset MFA
                            </button>
                        @else
                            <span style="font-size: 0.75rem; color: var(--ink-muted);">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--ink-muted); padding: 2.5rem;">
                        Belum ada akun pengguna yang terdaftar.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
    {{ $users->links() }}
</div>

<!-- ── MODAL TAMBAH SUPERADMIN ────────────────────────────────── -->
<div class="modal-backdrop" id="modal-add-user">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Tambah Akun Superadmin Baru</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-add-user')">✕</button>
        </div>
        <form action="{{ route('admin.master.users.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_name">Nama Lengkap Pegawai <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="name" id="add_name" class="form-control" placeholder="Contoh: Administrator Persandian" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_email">Alamat Email Dinas (@cirebonkota.go.id) <span style="color: var(--danger);">*</span></label>
                    <input type="email" name="email" id="add_email" class="form-control font-mono" placeholder="admin.baru@cirebonkota.go.id" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_password">Kata Sandi Kuat <span style="color: var(--danger);">*</span></label>
                    <input type="password" name="password" id="add_password" class="form-control font-mono" placeholder="Minimal 8 karakter (huruf, angka, simbol)" required>
                    <div class="form-helper">Wajib memenuhi standar kompleksitas keamanan password BSSN.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-add-user')">Batal</button>
                <button type="submit" class="btn btn-primary">Buat Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL SETUP TOTP MFA (GOOGLE AUTHENTICATOR) ─────────────── -->
<div class="modal-backdrop" id="modal-setup-mfa">
    <div class="modal-flat modal-lg">
        <div class="modal-header">
            <h3>Aktivasi Multi-Factor Authentication (TOTP / Google Authenticator)</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-setup-mfa')">✕</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 0.8125rem; color: var(--ink-secondary); margin-bottom: 1.25rem;">
                Tingkatkan keamanan akun Superadmin Anda dengan otentikasi dua faktor berbasis waktu (RFC 6238). Pindai QR Code atau masukkan Secret Key ke aplikasi <strong>Google Authenticator</strong> atau <strong>Microsoft Authenticator</strong>.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; background: var(--surface-1); border: 1px solid var(--hairline); padding: 1.25rem; margin-bottom: 1.25rem;">
                <!-- QR Code & Secret -->
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--ink); margin-bottom: 0.5rem; text-transform: uppercase;">
                        Langkah 1: Pindai QR Code
                    </div>
                    
                    <div id="mfa-qr-container" style="background: #fff; border: 1px solid var(--hairline); width: 180px; height: 180px; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: var(--ink-muted);">
                        Memuat QR Code...
                    </div>

                    <div style="margin-top: 0.75rem;">
                        <div style="font-size: 0.6875rem; color: var(--ink-secondary);">Secret Key (Input Manual):</div>
                        <code id="mfa-secret-key" class="font-mono" style="font-size: 0.875rem; font-weight: 700; color: var(--primary); letter-spacing: 0.1em;">-</code>
                        <button type="button" class="btn btn-outline btn-sm" style="margin-top: 4px; display: block; margin-left: auto; margin-right: auto;" onclick="copyMfaSecret()">
                            Salin Secret Key
                        </button>
                    </div>
                </div>

                <!-- Input Kode Konfirmasi -->
                <div>
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--ink); margin-bottom: 0.5rem; text-transform: uppercase;">
                        Langkah 2: Masukkan Kode OTP 6-Digit
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mfa-confirm-code">Kode 6-Digit dari Authenticator</label>
                        <input type="text" id="mfa-confirm-code" maxlength="6" pattern="\d{6}" class="form-control font-mono" style="font-size: 1.5rem; text-align: center; letter-spacing: 0.25em; font-weight: 700;" placeholder="000000">
                        <div class="form-helper">Masukkan 6 digit angka yang muncul pada aplikasi Authenticator Anda.</div>
                    </div>

                    <button type="button" class="btn btn-primary" style="width: 100%;" id="btn-confirm-mfa" onclick="confirmMfaSetup()">
                        ✓ Verifikasi & Aktifkan MFA
                    </button>
                </div>
            </div>

            <!-- Recovery Backup Codes -->
            <div id="recovery-codes-section" style="display: none; background: #defbe6; border: 1px solid #8be69e; padding: 1rem;">
                <div style="font-size: 0.8125rem; font-weight: 700; color: #0e6027; margin-bottom: 4px;">
                    ✓ MFA Berhasil Diaktifkan! Simpan Kode Pemulihan Darurat Anda:
                </div>
                <div style="font-size: 0.75rem; color: #0e6027; margin-bottom: 8px;">
                    Gunakan salah satu kode cadangan ini jika Anda kehilangan akses ke perangkat authenticator.
                </div>
                <div id="recovery-codes-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-family: 'IBM Plex Mono', monospace; font-size: 0.8125rem; font-weight: 600;">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-setup-mfa')">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentMfaSecret = '';

    function openModalMfaSetup() {
        openModal('modal-setup-mfa');
        document.getElementById('mfa-qr-container').innerHTML = 'Memuat QR Code...';
        document.getElementById('mfa-secret-key').textContent = 'Memuat...';

        fetch('{{ route("admin.mfa.setup") }}')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentMfaSecret = data.secret;
                    document.getElementById('mfa-secret-key').textContent = data.secret;
                    
                    // Simple pure SVG representation of QR Placeholder / QR rendering
                    const qrUrl = encodeURIComponent(data.qr_uri);
                    document.getElementById('mfa-qr-container').innerHTML = `
                        <div style="padding: 8px; text-align: center;">
                            <div style="font-size: 0.6875rem; color: #525252; margin-bottom: 4px;">Scan via Authenticator App</div>
                            <div style="background: #161616; color: #fff; padding: 12px; font-family: monospace; font-size: 0.75rem; word-break: break-all;">
                                ${data.secret}
                            </div>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error('Gagal memuat MFA setup:', err);
                window.showToast('Gagal memuat data setup MFA.', 'danger');
            });
    }

    function copyMfaSecret() {
        if (currentMfaSecret) {
            window.copyToClipboard(currentMfaSecret, 'Secret Key MFA berhasil disalin!');
        }
    }

    function confirmMfaSetup() {
        const code = document.getElementById('mfa-confirm-code').value.trim();
        if (!code || code.length !== 6) {
            window.showToast('Masukkan 6 digit kode OTP yang valid.', 'danger');
            return;
        }

        const btn = document.getElementById('btn-confirm-mfa');
        btn.disabled = true;
        btn.textContent = 'Memverifikasi...';

        fetch('{{ route("admin.mfa.confirm") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ code: code })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('Multi-Factor Authentication berhasil diaktifkan!', 'success');
                document.getElementById('recovery-codes-section').style.display = 'block';
                setTimeout(() => location.reload(), 2500);
            } else {
                window.showToast(data.message || 'Kode OTP salah.', 'danger');
            }
        })
        .catch(err => {
            console.error('Gagal verifikasi MFA:', err);
            window.showToast('Terjadi kesalahan verifikasi MFA.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '✓ Verifikasi & Aktifkan MFA';
        });
    }

    function disableMfaUser(userId, email) {
        if (!confirm('Apakah Anda yakin ingin me-reset (menonaktifkan) MFA untuk akun: ' + email + '?')) {
            return;
        }

        fetch('/admin/master/users/' + userId + '/disable-mfa', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            }
        })
        .catch(err => {
            console.error('Gagal nonaktifkan MFA:', err);
            window.showToast('Gagal menonaktifkan MFA.', 'danger');
        });
    }
</script>
@endpush
