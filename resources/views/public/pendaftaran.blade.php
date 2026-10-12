@extends('layouts.public')

@section('title', 'Formulir Pendaftaran Mandiri TTE ASN Kota Cirebon')

@section('content')
<div class="card-flat" style="border-top: 4px solid var(--primary);">
    <div class="panel-header" style="flex-direction: column; align-items: flex-start; gap: 0.25rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700;">Formulir Pendaftaran Sertifikat Elektronik (TTE)</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-secondary);">
            Layanan resmi permohonan sertifikat TTE BSrE dan penerbitan email dinas resmi Pemerintah Kota Cirebon.
        </p>
    </div>

    <!-- Draft Auto-Save Banner -->
    <div id="draft-alert" style="display: none; background: #edf5ff; border: 1px solid #a6c8ff; padding: 8px 12px; margin-bottom: 1.25rem; font-size: 0.75rem; color: #0043ce; justify-content: space-between; align-items: center;">
        <span>Ditemukan draft pengisian sebelumnya yang tersimpan di browser Anda.</span>
        <button type="button" class="btn btn-ghost btn-sm" onclick="clearDraft()" style="color: var(--danger); font-weight: 600;">Hapus Draft</button>
    </div>

    <form action="{{ route('pendaftaran.store') }}" method="POST" id="form-pendaftaran">
        @csrf

        {{-- ── DATA IDENTITAS PEGAWAI ASN ────────────────────── --}}
        <div style="margin-bottom: 2rem;">
            <div class="form-group">
                <label class="form-label" for="nama_lengkap">Nama Lengkap Sesuai KTP <span style="color: var(--danger);">*</span></label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap') }}" placeholder="Contoh: Budi Santoso" required>
                <div class="form-helper">Isi nama lengkap sesuai KTP tanpa gelar akademik/keagamaan dan tanpa tanda baca titik (.) atau koma (,).</div>
                @error('nama_lengkap') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            {{-- ── IDENTITAS NIK & NIP BESERTA WARNING BANNER DIBAWAH FIELD NIP ── --}}
            @php
                $nikErr = $errors->first('nik');
                $nipErr = $errors->first('nip');
                $nikSudahTerdata = $errors->has('nik') && str_contains($nikErr, 'sudah terdata');
                $nipSudahTerdata = $errors->has('nip') && str_contains($nipErr, 'sudah terdata');
                $isDataSudahMasuk = $nikSudahTerdata || $nipSudahTerdata;

                // Hanya tampilkan pesan error format jika bukan duplicate error
                $nikFormatError = ($errors->has('nik') && !$nikSudahTerdata) ? $nikErr : null;
                $nipFormatError = ($errors->has('nip') && !$nipSudahTerdata) ? $nipErr : null;
            @endphp

            <!-- 1. Field NIK -->
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="nik">Nomor Induk Kependudukan (NIK 16 Digit) <span style="color: var(--danger);">*</span></label>
                <input type="text" name="nik" id="nik" maxlength="16" pattern="\d{16}" class="form-control font-mono {{ $nikFormatError ? 'is-invalid' : '' }}" value="{{ old('nik') }}" placeholder="3274xxxxxxxxxxxx" required>
                <div class="form-helper">Wajib 16 digit angka sesuai KTP.</div>
                @if($nikFormatError)
                    <div class="form-error">{{ $nikFormatError }}</div>
                @endif
            </div>

            <!-- 2. Field NIP -->
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="nip">Nomor Induk Pegawai (NIP 18 Digit) <span style="color: var(--danger);">*</span></label>
                <input type="text" name="nip" id="nip" maxlength="18" pattern="\d{18}" class="form-control font-mono {{ $nipFormatError ? 'is-invalid' : '' }}" value="{{ old('nip') }}" placeholder="1990xxxxxxxxxxxxxx" required>
                <div class="form-helper">Wajib 18 digit angka NIP resmi BKN.</div>
                @if($nipFormatError)
                    <div class="form-error">{{ $nipFormatError }}</div>
                @endif
            </div>

            <!-- 3. Warning NIP/NIK Sudah Terdata -->
            <div id="warning-nik-nip-banner" style="{{ $isDataSudahMasuk ? 'display: block;' : 'display: none;' }} margin-bottom: 1.25rem;">
                <div class="alert-flat alert-warning" style="margin-bottom: 0; display: flex; align-items: center; gap: 0.625rem; font-weight: 600; font-size: 0.875rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink: 0; color: #b28900;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <span>NIP/NIK sudah terdata</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="no_hp">Nomor WhatsApp Aktif <span style="color: var(--danger);">*</span></label>
                    <input type="tel" name="no_hp" id="no_hp" class="form-control font-mono @error('no_hp') is-invalid @enderror" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx" required>
                    @error('no_hp') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="skpd_id">Unit Kerja (SKPD) <span style="color: var(--danger);">*</span></label>
                    <select name="skpd_id" id="skpd_id" class="form-select @error('skpd_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Unit Kerja / SKPD --</option>
                        @foreach($skpdList as $skpd)
                            <option value="{{ $skpd->id }}" {{ old('skpd_id') == $skpd->id ? 'selected' : '' }}>
                                {{ $skpd->nama_skpd }}
                            </option>
                        @endforeach
                    </select>
                    @error('skpd_id') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label" for="deskripsi">
                    Deskripsi Penempatan Kerja
                    <span style="font-size: 0.75rem; color: var(--ink-muted); font-weight: normal;">(Tambahan Informasi Detail Penempatan Kerja)</span>
                </label>
                <textarea name="deskripsi" id="deskripsi" rows="2" class="form-control @error('deskripsi') is-invalid @enderror" placeholder="Contoh: Puskesmas Kesambi, SDN 1 Kejaksan, RSUD Gunung Jati, Bidang Penataan Ruang, dll. (Diisi manual oleh ASN)">{{ old('deskripsi') }}</textarea>
                @error('deskripsi') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-helper">Isi secara manual jika lokasi atau unit penempatan tugas Anda memerlukan informasi detail spesifik di bawah SKPD terkait.</div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="jabatan_id">Jabatan ASN</label>
                    <select name="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($jabatanList as $jabatan)
                            <option value="{{ $jabatan->id }}" {{ old('jabatan_id') == $jabatan->id ? 'selected' : '' }}>
                                {{ $jabatan->nama_label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="golongan_id">Pangkat / Golongan</label>
                    <select name="golongan_id" id="golongan_id" class="form-select @error('golongan_id') is-invalid @enderror">
                        <option value="">-- Pilih Golongan --</option>
                        @foreach($golonganList as $golongan)
                            <option value="{{ $golongan->id }}" {{ old('golongan_id') == $golongan->id ? 'selected' : '' }}>
                                {{ $golongan->nama_label }} ({{ $golongan->kode }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ── STATUS EMAIL DINAS ───────────────────────── --}}
        <div style="margin-bottom: 2rem;">
            <div class="form-group">
                <div class="radio-tile-group">
                    <div>
                        <input type="radio" name="status_memiliki_email" id="email_status_belum" value="0" class="radio-tile-input" style="display:none;" {{ old('status_memiliki_email', '0') == '0' ? 'checked' : '' }} onchange="toggleEmailOption(false)">
                        <label for="email_status_belum" class="radio-tile-label">
                            <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 4px;">Belum Memiliki Email Dinas</div>
                            <div style="font-size: 0.75rem; color: var(--ink-secondary);">Sistem akan membuatkan usulan akun email dinas baru resmi secara otomatis.</div>
                        </label>
                    </div>
                    <div>
                        <input type="radio" name="status_memiliki_email" id="email_status_sudah" value="1" class="radio-tile-input" style="display:none;" {{ old('status_memiliki_email') == '1' ? 'checked' : '' }} onchange="toggleEmailOption(true)">
                        <label for="email_status_sudah" class="radio-tile-label">
                            <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 4px;">Sudah Memiliki Email Dinas</div>
                            <div style="font-size: 0.75rem; color: var(--ink-secondary);">Saya telah memiliki akun email aktif berakhiran @cirebonkota.go.id.</div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Input Email Existing -->
            <div id="wrapper-email-existing" style="display: none; margin-top: 1rem;">
                <label class="form-label" for="email_existing">Alamat Email Dinas Aktif <span style="color: var(--danger);">*</span></label>
                <input type="email" name="email_existing" id="email_existing" class="form-control font-mono @error('email_existing') is-invalid @enderror" value="{{ old('email_existing') }}" placeholder="nama.anda@cirebonkota.go.id">
                <div class="form-helper">Wajib menggunakan domain resmi instansi (@cirebonkota.go.id).</div>
                @error('email_existing') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- ── VERIFIKASI KEAMANAN (Google reCAPTCHA) ─────────────── --}}
        <div style="margin-bottom: 2rem;">
            @if(config('services.recaptcha.site_key'))
                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    @error('g-recaptcha-response') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            @endif
            @error('captcha') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <!-- Tombol Submit -->
        <div style="border-top: 1px solid var(--hairline); padding-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary btn-lg" id="btn-submit" style="width: 100%; max-width: 300px;">
                Kirim Permohonan TTE
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    const DRAFT_KEY = 'tte_registration_draft_temp';

    function toggleEmailOption(hasEmail) {
        if (hasEmail) {
            document.getElementById('wrapper-email-existing').style.display = 'block';
        } else {
            document.getElementById('wrapper-email-existing').style.display = 'none';
        }
        saveDraft();
    }

    // SessionStorage Auto-Save & Restore Engine (Aman Tanpa PII NIK/NIP Mentah)
    function saveDraft() {
        const draft = {
            nama_lengkap: document.getElementById('nama_lengkap').value,
            // NIK dan NIP sengaja TIDAK disimpan di storage browser demi kepatuhan PDP & BSSN
            no_hp: document.getElementById('no_hp').value,
            skpd_id: document.getElementById('skpd_id').value,
            deskripsi: document.getElementById('deskripsi')?.value || '',
            jabatan_id: document.getElementById('jabatan_id').value,
            golongan_id: document.getElementById('golongan_id').value,
            status_memiliki_email: document.querySelector('input[name="status_memiliki_email"]:checked')?.value || '0',
            email_existing: document.getElementById('email_existing').value,
        };
        sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    }

    function restoreDraft() {
        const raw = sessionStorage.getItem(DRAFT_KEY);
        if (!raw) return;

        try {
            const draft = JSON.parse(raw);
            if (draft.nama_lengkap) document.getElementById('nama_lengkap').value = draft.nama_lengkap;
            if (draft.no_hp) document.getElementById('no_hp').value = draft.no_hp;
            if (draft.skpd_id) {
                document.getElementById('skpd_id').value = draft.skpd_id;
            }
            if (draft.deskripsi && document.getElementById('deskripsi')) {
                document.getElementById('deskripsi').value = draft.deskripsi;
            }
            if (draft.jabatan_id) document.getElementById('jabatan_id').value = draft.jabatan_id;
            if (draft.golongan_id) document.getElementById('golongan_id').value = draft.golongan_id;
            if (draft.email_existing) document.getElementById('email_existing').value = draft.email_existing;

            if (draft.status_memiliki_email === '1') {
                document.getElementById('email_status_sudah').checked = true;
                toggleEmailOption(true);
            } else {
                document.getElementById('email_status_belum').checked = true;
                toggleEmailOption(false);
            }

            document.getElementById('draft-alert').style.display = 'flex';
        } catch (e) {
            console.error('Gagal memulihkan draft:', e);
        }
    }

    function clearDraft() {
        sessionStorage.removeItem(DRAFT_KEY);
        document.getElementById('draft-alert').style.display = 'none';
        document.getElementById('form-pendaftaran').reset();
    }

    // Event listeners untuk auto-save
    document.getElementById('nama_lengkap').addEventListener('input', saveDraft);
    document.getElementById('nip').addEventListener('input', saveDraft);
    document.getElementById('no_hp').addEventListener('input', saveDraft);
    document.getElementById('skpd_id').addEventListener('change', saveDraft);
    document.getElementById('deskripsi')?.addEventListener('input', saveDraft);
    document.getElementById('jabatan_id').addEventListener('change', saveDraft);
    document.getElementById('golongan_id').addEventListener('change', saveDraft);

    // Pembersihan draft otomatis saat submit formulir berhasil
    document.getElementById('form-pendaftaran').addEventListener('submit', function () {
        sessionStorage.removeItem(DRAFT_KEY);
    });

    // Sembunyikan warning banner jika pengguna mengetik ulang NIK atau NIP
    const warningBanner = document.getElementById('warning-nik-nip-banner');
    if (warningBanner) {
        document.getElementById('nik')?.addEventListener('input', function () {
            warningBanner.style.display = 'none';
        });
        document.getElementById('nip')?.addEventListener('input', function () {
            warningBanner.style.display = 'none';
        });
    }

    // Initialize on load
    restoreDraft();

    // Inisialisasi Fitur Pencarian / Autocomplete untuk SKPD, Jabatan, dan Golongan
    if (typeof window.initSearchableCombobox === 'function') {
        window.initSearchableCombobox(document.getElementById('skpd_id'), {
            placeholder: '🔍 Cari Unit Kerja / SKPD...'
        });
        window.initSearchableCombobox(document.getElementById('jabatan_id'), {
            placeholder: '🔍 Cari Jabatan ASN...'
        });
        window.initSearchableCombobox(document.getElementById('golongan_id'), {
            placeholder: '🔍 Cari Pangkat / Golongan...'
        });
    }
</script>
@endpush
