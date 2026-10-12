@extends('layouts.admin')

@section('title', 'Penerbitan Tahap 2 & Manajemen Sertifikat TTE')
@section('breadcrumb', 'Penerbitan Sertifikat TTE')

@section('content')
<div class="panel-header">
    <div>
        <h1 class="panel-title">Penerbitan Sertifikat Elektronik & Manajemen Siklus Hidup (Tahap 2)</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Penerbitan sertifikat TTE BSrE (masa aktif 2 tahun penuh) dan pemantauan masa kedaluwarsa berkala (H-7 Warning).
        </p>
    </div>
</div>

<!-- ── SEKSI 1: ANTRIAN PENERBITAN TAHAP 2 ────────────────────── -->
<div class="card-flat" style="border-top: 4px solid var(--primary); margin-bottom: 2rem;">
    <div class="panel-header">
        <div>
            <h2 style="font-size: 1rem; font-weight: 600;">Antrian Penerbitan Sertifikat (Tahap 2)</h2>
            <div style="font-size: 0.75rem; color: var(--ink-muted);">
                Permohonan ASN yang telah disetujui pada Tahap 1 dan siap diterbitkan sertifikat TTE-nya.
            </div>
        </div>
        <span class="badge badge-menunggu font-mono">{{ $antrianTahap2->count() }} Permohonan Siap</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 140px;">No. Referensi</th>
                    <th>Nama Lengkap ASN</th>
                    <th>Unit Kerja (SKPD)</th>
                    <th>Email Dinas Terverifikasi</th>
                    <th>Tgl Persetujuan</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($antrianTahap2 as $antrian)
                    <tr>
                        <td class="font-mono" style="font-weight: 600; color: var(--primary);">
                            {{ $antrian->nomor_referensi }}
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $antrian->nama_lengkap }}</div>
                            <div style="font-size: 0.6875rem; color: var(--ink-muted);" class="font-mono">NIP: {{ $antrian->nip }}</div>
                        </td>
                        <td>{{ $antrian->skpd->nama_skpd ?? '-' }}</td>
                        <td class="font-mono" style="font-size: 0.8125rem; font-weight: 600; color: #0043ce;">
                            {{ $antrian->email_final_terverifikasi ?? $antrian->email_existing }}
                        </td>
                        <td class="font-mono" style="font-size: 0.75rem;">
                            {{ $antrian->verified_at?->format('d/m/Y H:i') }}
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="btn btn-primary btn-sm" onclick="openModalTerbitkan('{{ $antrian->uuid ?? $antrian->nomor_referensi }}', '{{ addslashes($antrian->nama_lengkap) }}', '{{ $antrian->email_final_terverifikasi ?? $antrian->email_existing }}')">
                                🔑 Terbitkan Sertifikat TTE
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--ink-muted); padding: 2rem;">
                            Tidak ada antrian pendaftaran yang menunggu penerbitan sertifikat Tahap 2.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ── SEKSI 2: MONITORING SIKLUS HIDUP SERTIFIKAT AKTIF ──────── -->
<div class="card-flat">
    <div class="panel-header">
        <div>
            <h2 style="font-size: 1rem; font-weight: 600;">Daftar Sertifikat TTE Terbit & Siklus Hidup</h2>
            <div style="font-size: 0.75rem; color: var(--ink-muted);">
                Pemantauan masa berlaku 2 tahun, zona peringatan H-7 kedaluwarsa, dan status pencabutan sertifikat.
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('admin.penerbitan.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <a href="{{ route('admin.penerbitan.index') }}" class="btn btn-sm {{ empty($statusFilter) ? 'btn-primary' : 'btn-outline' }}">
                Semua
            </a>
            <a href="{{ route('admin.penerbitan.index', ['status' => 'AKTIF']) }}" class="btn btn-sm {{ $statusFilter === 'AKTIF' ? 'btn-primary' : 'btn-outline' }}">
                Aktif
            </a>
            <a href="{{ route('admin.penerbitan.index', ['status' => 'WARNING_H7']) }}" class="btn btn-sm {{ ($statusFilter === 'WARNING_H7' || $statusFilter === 'WARNING_H3') ? 'btn-primary' : 'btn-outline' }}">
                Peringatan H-7
            </a>
            <a href="{{ route('admin.penerbitan.index', ['status' => 'EXPIRED']) }}" class="btn btn-sm {{ $statusFilter === 'EXPIRED' ? 'btn-primary' : 'btn-outline' }}">
                Kedaluwarsa
            </a>
            <a href="{{ route('admin.penerbitan.index', ['status' => 'REVOKED']) }}" class="btn btn-sm {{ $statusFilter === 'REVOKED' ? 'btn-primary' : 'btn-outline' }}">
                Dicabut (Revoked)
            </a>
        </div>

        <div>
            <select name="skpd_id" class="form-select" style="max-width: 240px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;" onchange="this.form.submit()">
                <option value="">-- Filter SKPD --</option>
                @foreach($skpdList as $skpd)
                    <option value="{{ $skpd->id }}" {{ request('skpd_id') == $skpd->id ? 'selected' : '' }}>
                        {{ $skpd->nama_skpd }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Pegawai (ASN)</th>
                    <th>Unit Kerja (SKPD)</th>
                    <th>Email Dinas Resmi</th>
                    <th>Tgl Penerbitan</th>
                    <th>Tgl Kadaluarsa (+2 Thn)</th>
                    <th>Status Sertifikat</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sertifikatList as $cert)
                    <tr class="{{ in_array($cert->status_sertifikat, ['WARNING_H7', 'WARNING_H3']) ? 'is-warning' : '' }}">
                        <td>
                            <div style="font-weight: 600;">{{ $cert->pendaftaran->nama_lengkap ?? '-' }}</div>
                            <div style="font-size: 0.6875rem; color: var(--ink-muted);" class="font-mono">
                                NIP: {{ $cert->pendaftaran->nip ?? '-' }}
                            </div>
                        </td>
                        <td>{{ $cert->pendaftaran->skpd->nama_skpd ?? '-' }}</td>
                        <td class="font-mono" style="font-size: 0.75rem;">{{ $cert->email_dinas }}</td>
                        <td class="font-mono" style="font-size: 0.75rem;">
                            {{ $cert->tanggal_penerbitan?->format('d/m/Y') }}
                        </td>
                        <td class="font-mono" style="font-size: 0.75rem; font-weight: 600;">
                            {{ $cert->tanggal_kadaluarsa?->format('d/m/Y') }}
                        </td>
                        <td>
                            @if($cert->status_sertifikat === 'AKTIF')
                                <span class="badge badge-aktif">Aktif</span>
                            @elseif($cert->status_sertifikat === 'WARNING_H7' || $cert->status_sertifikat === 'WARNING_H3')
                                @php $badge = $cert->status_masa_berlaku_badge; @endphp
                                <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            @elseif($cert->status_sertifikat === 'EXPIRED')
                                <span class="badge badge-expired">Kedaluwarsa</span>
                            @elseif($cert->status_sertifikat === 'REVOKED')
                                <span class="badge badge-revoked">Dicabut</span>
                            @elseif($cert->status_sertifikat === 'PERLU_PERPANJANG')
                                <span class="badge" style="background:#fff1f1; color:#da1e28; border:1px solid #da1e28;">Perlu Perpanjang</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($cert->status_sertifikat === 'AKTIF' || $cert->status_sertifikat === 'WARNING_H7' || $cert->status_sertifikat === 'WARNING_H3')
                                <button type="button" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--hairline);" onclick="updateStatusSertifikat('{{ $cert->uuid }}', 'REVOKED')">
                                    Cabut (Revoke)
                                </button>
                            @else
                                <button type="button" class="btn btn-outline btn-sm" onclick="updateStatusSertifikat('{{ $cert->uuid }}', 'AKTIF')">
                                    Aktifkan
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--ink-muted); padding: 2.5rem;">
                            Belum ada data sertifikat TTE yang tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
        {{ $sertifikatList->links('vendor.pagination.carbon') }}
    </div>
</div>

<!-- ── MODAL PENERBITAN SERTIFIKAT TTE TAHAP 2 ────────────────── -->
<div class="modal-backdrop" id="modal-terbitkan">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Penerbitan Sertifikat TTE BSrE (Tahap 2)</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-terbitkan')">✕</button>
        </div>
        <form id="form-terbitkan" method="POST">
            @csrf
            <div class="modal-body">
                <div style="background: var(--surface-1); border: 1px solid var(--hairline); padding: 0.875rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.75rem; color: var(--ink-secondary);">Pemohon ASN:</div>
                    <div id="modal-asn-name" style="font-size: 0.9375rem; font-weight: 700;">-</div>
                    <div id="modal-asn-email" class="font-mono" style="font-size: 0.8125rem; color: var(--primary); margin-top: 2px;">-</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tanggal_penerbitan">Tanggal Penerbitan Sertifikat <span style="color: var(--danger);">*</span></label>
                    <input type="date" name="tanggal_penerbitan" id="tanggal_penerbitan" class="form-control font-mono" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required onchange="calculateExpiryDate(this.value)">
                    <div class="form-helper">Tanggal penerbitan resmi oleh Balai Sertifikasi Elektronik (BSrE).</div>
                </div>

                <!-- Preview Kalkulasi Otomatis Masa Berlaku 2 Tahun -->
                <div style="background: #edf5ff; border: 1px solid #a6c8ff; padding: 0.875rem; margin-top: 1rem;">
                    <div style="font-size: 0.75rem; color: #0043ce; font-weight: 600;">Masa Berlaku Otomatis (2 Tahun Penuh):</div>
                    <div style="font-size: 1.125rem; font-weight: 700; color: #002d9c; margin-top: 2px;" id="modal-expiry-preview" class="font-mono">
                        {{ date('d F Y', strtotime('+2 years')) }}
                    </div>
                    <div style="font-size: 0.6875rem; color: #0043ce; margin-top: 4px;">
                        Sistem akan otomatis memantau dan memicu Peringatan H-7 kedaluwarsa.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-terbitkan')">Batal</button>
                <button type="submit" class="btn btn-primary">Konfirmasi Penerbitan Sertifikat</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModalTerbitkan(pendaftaranId, nama, email) {
        document.getElementById('modal-asn-name').textContent = nama;
        document.getElementById('modal-asn-email').textContent = email;
        document.getElementById('form-terbitkan').action = '/admin/penerbitan/' + pendaftaranId;
        openModal('modal-terbitkan');
    }

    function calculateExpiryDate(dateVal) {
        if (!dateVal) return;
        const d = new Date(dateVal);
        d.setFullYear(d.getFullYear() + 2);
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        document.getElementById('modal-expiry-preview').textContent = d.toLocaleDateString('id-ID', options);
    }

    function updateStatusSertifikat(sertifikatId, status) {
        if (!confirm('Apakah Anda yakin ingin mengubah status sertifikat ini menjadi ' + status + '?')) {
            return;
        }

        fetch('/admin/penerbitan/sertifikat/' + sertifikatId + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status_sertifikat: status })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                window.showToast(data.message || 'Gagal mengubah status sertifikat.', 'danger');
            }
        })
        .catch(err => {
            console.error('Gagal update status:', err);
            window.showToast('Terjadi kesalahan jaringan.', 'danger');
        });
    }
</script>
@endpush
