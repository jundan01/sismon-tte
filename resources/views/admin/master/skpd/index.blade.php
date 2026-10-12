@extends('layouts.admin')

@section('title', 'Master Data Unit Kerja (SKPD)')
@section('breadcrumb', 'Master Unit Kerja (SKPD)')

@section('content')
<div class="panel-header">
    <div>
        <h1 class="panel-title">Master Data Unit Kerja (SKPD)</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Daftar Perangkat Daerah dan Instansi Resmi di lingkungan Pemerintah Kota Cirebon.
        </p>
    </div>

    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal-add-skpd')">
        + Tambah SKPD Baru
    </button>
</div>

<!-- ── PENCARIAN ─────────────────────────────────────────────── -->
<div class="card-flat" style="padding: 1rem; margin-bottom: 1rem;">
    <form action="{{ route('admin.master.skpd.index') }}" method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
        <input type="text" name="q" class="form-control" style="max-width: 320px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;" placeholder="Cari nama atau kode SKPD..." value="{{ request('q') }}">
        <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
    </form>
</div>

<!-- ── TABEL SKPD ────────────────────────────────────────────── -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 140px;">Kode SKPD</th>
                <th>Nama Instansi / Perangkat Daerah</th>
                <th style="text-align: right; width: 120px;">Total ASN</th>
                <th style="text-align: right; width: 140px;">Total Pemohon</th>
                <th style="width: 100px;">Status</th>
                <th style="text-align: right; width: 180px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($skpdList as $skpd)
                <tr>
                    <td class="font-mono" style="font-weight: 600;">{{ $skpd->kode_skpd }}</td>
                    <td style="font-weight: 600;">{{ $skpd->nama_skpd }}</td>
                    <td style="text-align: right;" class="font-mono">
                        @if($skpd->total_asn > 0)
                            {{ number_format($skpd->total_asn) }}
                        @else
                            <span style="color: var(--ink-muted); font-style: italic; font-size: 0.75rem;">Belum diset</span>
                        @endif
                    </td>
                    <td style="text-align: right;" class="font-mono">{{ number_format($skpd->pendaftaran_tte_count ?? 0) }}</td>
                    <td>
                        @if($skpd->is_active)
                            <span class="badge badge-aktif">Aktif</span>
                        @else
                            <span class="badge badge-revoked">Nonaktif</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="openModalEdit('{{ $skpd->uuid }}', '{{ $skpd->kode_skpd }}', '{{ addslashes($skpd->nama_skpd) }}', {{ $skpd->is_active ? 'true' : 'false' }}, {{ $skpd->total_asn ?? 0 }})">
                            Edit
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleStatusSkpd('{{ $skpd->uuid }}')">
                            {{ $skpd->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--ink-muted); padding: 2.5rem;">
                        Belum ada data SKPD yang tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
    {{ $skpdList->links() }}
</div>

<!-- ── MODAL TAMBAH SKPD ──────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-add-skpd">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Tambah Master Unit Kerja (SKPD)</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-add-skpd')">✕</button>
        </div>
        <form action="{{ route('admin.master.skpd.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_kode_skpd">Kode SKPD <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="kode_skpd" id="add_kode_skpd" class="form-control font-mono" placeholder="Contoh: DISKOMINFO" required>
                    <div class="form-helper">Gunakan huruf kapital tanpa spasi.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_nama_skpd">Nama Lengkap Instansi / SKPD <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="nama_skpd" id="add_nama_skpd" class="form-control" placeholder="Contoh: Dinas Komunikasi dan Informatika" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_total_asn">Jumlah Total Pegawai (ASN)</label>
                    <input type="number" name="total_asn" id="add_total_asn" class="form-control font-mono" placeholder="Contoh: 45" min="0" value="0">
                    <div class="form-helper">Jumlah pegawai ASN aktif di unit kerja ini. Digunakan untuk menghitung persentase capaian adopsi TTE.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-add-skpd')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan SKPD</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL EDIT SKPD ────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-edit-skpd">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Edit Data Unit Kerja (SKPD)</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-edit-skpd')">✕</button>
        </div>
        <form id="form-edit-skpd" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_kode_skpd">Kode SKPD <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="kode_skpd" id="edit_kode_skpd" class="form-control font-mono" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_nama_skpd">Nama Lengkap Instansi / SKPD <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="nama_skpd" id="edit_nama_skpd" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_total_asn">Jumlah Total Pegawai (ASN)</label>
                    <input type="number" name="total_asn" id="edit_total_asn" class="form-control font-mono" min="0" value="0">
                    <div class="form-helper">Jumlah pegawai ASN aktif di unit kerja ini. Digunakan untuk menghitung persentase capaian adopsi TTE.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit-skpd')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModalEdit(id, kode, nama, isActive, totalAsn) {
        document.getElementById('edit_kode_skpd').value = kode;
        document.getElementById('edit_nama_skpd').value = nama;
        document.getElementById('edit_total_asn').value = totalAsn || 0;
        document.getElementById('form-edit-skpd').action = '/admin/master/skpd/' + id;
        openModal('modal-edit-skpd');
    }

    function toggleStatusSkpd(id) {
        fetch('/admin/master/skpd/' + id + '/toggle', {
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
            console.error('Gagal toggle status:', err);
            window.showToast('Gagal mengubah status SKPD.', 'danger');
        });
    }
</script>
@endpush
