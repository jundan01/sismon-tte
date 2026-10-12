@extends('layouts.admin')

@section('title', 'Master Data Jabatan & Golongan ASN')
@section('breadcrumb', 'Master Jabatan & Golongan')

@section('content')
<div class="panel-header">
    <div>
        <h1 class="panel-title">Master Data Jabatan & Golongan ASN</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Referensi standar jabatan struktural/fungsional dan jenjang pangkat golongan ASN.
        </p>
    </div>

    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal-add-item')">
        + Tambah Data Master
    </button>
</div>

<!-- ── FILTER BAR & PENCARIAN ─────────────────────────────────── -->
<div class="card-flat" style="padding: 1rem; margin-bottom: 1rem;">
    <form action="{{ route('admin.master.jabatan-golongan.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="{{ route('admin.master.jabatan-golongan.index') }}" class="btn btn-sm {{ empty($currentKategori) ? 'btn-primary' : 'btn-outline' }}">
                Semua Kategori
            </a>
            <a href="{{ route('admin.master.jabatan-golongan.index', ['kategori' => 'JABATAN']) }}" class="btn btn-sm {{ ($currentKategori ?? '') === 'JABATAN' ? 'btn-primary' : 'btn-outline' }}">
                Jabatan
            </a>
            <a href="{{ route('admin.master.jabatan-golongan.index', ['kategori' => 'GOLONGAN']) }}" class="btn btn-sm {{ ($currentKategori ?? '') === 'GOLONGAN' ? 'btn-primary' : 'btn-outline' }}">
                Golongan
            </a>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="q" class="form-control" style="max-width: 260px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;" placeholder="Cari nama atau kode..." value="{{ request('q') }}">
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
        </div>
    </form>
</div>

<!-- ── TABEL JABATAN & GOLONGAN ───────────────────────────────── -->
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 120px;">Kategori</th>
                <th style="width: 140px;">Kode Referensi</th>
                <th>Nama / Label</th>
                <th style="text-align: right; width: 160px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($list as $item)
                <tr>
                    <td>
                        @if($item->kategori === 'JABATAN')
                            <span class="badge badge-menunggu">JABATAN</span>
                        @else
                            <span class="badge" style="background:#f4f4f4; border:1px solid #8d8d8d;">GOLONGAN</span>
                        @endif
                    </td>
                    <td class="font-mono" style="font-weight: 600;">{{ $item->kode }}</td>
                    <td style="font-weight: 600;">{{ $item->nama_label }}</td>
                    <td style="text-align: right;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="openModalEdit('{{ $item->uuid ?? $item->id }}', '{{ $item->kode }}', '{{ addslashes($item->nama_label) }}')">
                            Edit
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--hairline);" onclick="deleteItem('{{ $item->uuid ?? $item->id }}', '{{ addslashes($item->nama_label) }}')">
                            Hapus
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--ink-muted); padding: 2.5rem;">
                        Belum ada data master yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
    {{ $list->links() }}
</div>

<!-- ── MODAL TAMBAH DATA ──────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-add-item">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Tambah Master Jabatan / Golongan</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-add-item')">✕</button>
        </div>
        <form action="{{ route('admin.master.jabatan-golongan.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_kategori">Kategori <span style="color: var(--danger);">*</span></label>
                    <select name="kategori" id="add_kategori" class="form-select" required>
                        <option value="JABATAN">JABATAN</option>
                        <option value="GOLONGAN">GOLONGAN</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_kode">Kode Referensi <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="kode" id="add_kode" class="form-control font-mono" placeholder="Contoh: IV/a atau JAB_STRUKTURAL" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_nama_label">Nama / Label Lengkap <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="nama_label" id="add_nama_label" class="form-control" placeholder="Contoh: Pembina (IV/a) atau Kepala Dinas" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-add-item')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Master Data</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL EDIT DATA ────────────────────────────────────────── -->
<div class="modal-backdrop" id="modal-edit-item">
    <div class="modal-flat">
        <div class="modal-header">
            <h3>Edit Master Jabatan / Golongan</h3>
            <button type="button" class="modal-close" onclick="closeModal('modal-edit-item')">✕</button>
        </div>
        <form id="form-edit-item" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_kode">Kode Referensi <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="kode" id="edit_kode" class="form-control font-mono" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_nama_label">Nama / Label Lengkap <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="nama_label" id="edit_nama_label" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit-item')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModalEdit(id, kode, label) {
        document.getElementById('edit_kode').value = kode;
        document.getElementById('edit_nama_label').value = label;
        document.getElementById('form-edit-item').action = '/admin/master/jabatan-golongan/' + id;
        openModal('modal-edit-item');
    }

    function deleteItem(id, label) {
        if (!confirm('Apakah Anda yakin ingin menghapus data master: ' + label + '?')) {
            return;
        }

        fetch('/admin/master/jabatan-golongan/' + id, {
            method: 'DELETE',
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
            } else {
                window.showToast(data.message || 'Gagal menghapus data master.', 'danger');
            }
        })
        .catch(err => {
            console.error('Gagal hapus item:', err);
            window.showToast('Gagal menghapus data master.', 'danger');
        });
    }
</script>
@endpush
