@extends('layouts.admin')

@section('title', 'Verifikasi Tahap 1 — Antrian Permohonan ASN')
@section('breadcrumb', 'Verifikasi Tahap 1')

@section('content')
<div class="panel-header">
    <div>
        <h1 class="panel-title">Antrian Verifikasi Berkas Pemohon (Tahap 1)</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Verifikasi identitas ASN, keabsahan surat rekomendasi, dan rekapitulasi akun email dinas resmi (@cirebonkota.go.id).
        </p>
    </div>
</div>

<!-- ── FILTER BAR & PENCARIAN ─────────────────────────────────── -->
<div class="card-flat" style="padding: 1rem; margin-bottom: 1rem;">
    <form action="{{ route('admin.verifikasi.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <!-- Status Tabs -->
            <a href="{{ route('admin.verifikasi.index', ['status' => 'MENUNGGU_VERIFIKASI']) }}" class="btn btn-sm {{ ($currentStatus ?? 'MENUNGGU_VERIFIKASI') === 'MENUNGGU_VERIFIKASI' ? 'btn-primary' : 'btn-outline' }}">
                Menunggu Verifikasi
            </a>
            <a href="{{ route('admin.verifikasi.index', ['status' => 'DISETUJUI']) }}" class="btn btn-sm {{ ($currentStatus ?? '') === 'DISETUJUI' ? 'btn-primary' : 'btn-outline' }}">
                Disetujui
            </a>
            <a href="{{ route('admin.verifikasi.index', ['status' => 'DITOLAK']) }}" class="btn btn-sm {{ ($currentStatus ?? '') === 'DITOLAK' ? 'btn-primary' : 'btn-outline' }}">
                Ditolak
            </a>
            <a href="{{ route('admin.verifikasi.index', ['status' => '']) }}" class="btn btn-sm {{ empty($currentStatus) ? 'btn-primary' : 'btn-outline' }}">
                Semua Status
            </a>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <select name="skpd_id" class="form-select" style="max-width: 220px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;" onchange="this.form.submit()">
                <option value="">-- Filter Unit Kerja (SKPD) --</option>
                @foreach($skpdList as $skpd)
                    <option value="{{ $skpd->id }}" {{ request('skpd_id') == $skpd->id ? 'selected' : '' }}>
                        {{ $skpd->nama_skpd }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="q" class="form-control" style="max-width: 250px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;" placeholder="Cari Nama / NIP / Deskripsi / Ref..." value="{{ request('q') }}">
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            @if(request('q') || request('deskripsi'))
                <a href="{{ route('admin.verifikasi.index', ['status' => $currentStatus, 'skpd_id' => request('skpd_id')]) }}" class="btn btn-outline btn-sm" style="color: var(--danger);" title="Reset Pencarian">✕</a>
            @endif
        </div>
    </form>

    @if(request('q') || request('deskripsi'))
        <div style="background: rgba(15, 98, 254, 0.05); border-left: 3px solid var(--primary); padding: 0.45rem 0.85rem; font-size: 0.75rem; color: var(--ink-secondary); margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
            <span>
                🔍 Menampilkan hasil pencarian: <strong>{{ request('q') ?: request('deskripsi') }}</strong> 
                (mencakup Nama, NIP/NIK, Nomor Referensi, dan <strong>Deskripsi Penempatan Kerja ASN</strong>)
            </span>
            <a href="{{ route('admin.verifikasi.index', ['status' => $currentStatus, 'skpd_id' => request('skpd_id')]) }}" style="color: var(--danger); text-decoration: none; font-weight: 600;">✕ Hapus Pencarian</a>
        </div>
    @endif
</div>

<!-- ── TABEL ANTRIAN FIFO ────────────────────────────────────── -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 140px;">No. Referensi</th>
                <th>Nama Lengkap ASN</th>
                <th>Unit Kerja (SKPD) & Penempatan</th>
                <th>Status Email Dinas</th>
                <th>Status Berkas</th>
                <th>Tgl Pendaftaran</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pendaftaranList as $item)
                <tr>
                    <td class="font-mono" style="font-weight: 600; color: var(--primary);">
                        {{ $item->nomor_referensi }}
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $item->nama_lengkap }}</div>
                        <div style="font-size: 0.6875rem; color: var(--ink-muted);" class="font-mono">
                            NIP: {{ $item->nip }}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $item->skpd->nama_skpd ?? '-' }}</div>
                        <div style="font-size: 0.6875rem; color: var(--ink-muted);">
                            {{ $item->jabatan->nama_label ?? '-' }}
                        </div>
                        @if(!empty($item->deskripsi))
                            <div style="margin-top: 4px; font-size: 0.75rem; color: var(--primary); background: rgba(15, 98, 254, 0.08); border: 1px solid rgba(15, 98, 254, 0.2); padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                📍 <strong>Penempatan:</strong> {{ $item->deskripsi }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($item->is_email_recap_confirmed)
                            <span class="badge badge-aktif">✓ Terekap</span>
                            <div class="font-mono" style="font-size: 0.6875rem; color: var(--ink-secondary); margin-top: 2px;">
                                {{ $item->email_final_terverifikasi }}
                            </div>
                        @elseif($item->status_memiliki_email)
                            <span class="badge" style="background:#e0e0e0; color:#161616;">Existing</span>
                            <div class="font-mono" style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 2px;">
                                {{ $item->email_existing }}
                            </div>
                        @else
                            <span class="badge badge-warning-h3">Belum Direkap</span>
                            <div class="font-mono" style="font-size: 0.6875rem; color: var(--primary); margin-top: 2px;">
                                Usulan: {{ $item->email_usulan }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($item->status === 'MENUNGGU_VERIFIKASI')
                            <span class="badge badge-menunggu">Menunggu</span>
                        @elseif($item->status === 'DISETUJUI')
                            <span class="badge badge-disetujui">Disetujui</span>
                        @elseif($item->status === 'DITOLAK')
                            <span class="badge badge-ditolak">Ditolak</span>
                        @endif
                    </td>
                    <td class="font-mono" style="font-size: 0.75rem;">
                        {{ $item->created_at?->format('d/m/Y H:i') }}
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('admin.verifikasi.show', $item->nomor_referensi) }}" class="btn btn-primary btn-sm">
                            Detail & Verifikasi →
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--ink-muted); padding: 2.5rem;">
                        Tidak ada data pendaftaran yang sesuai dengan filter pencarian.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination Links -->
<div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
    {{ $pendaftaranList->links('vendor.pagination.carbon') }}
</div>
@endsection
