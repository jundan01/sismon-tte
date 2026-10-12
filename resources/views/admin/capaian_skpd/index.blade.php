@extends('layouts.admin')

@section('title', 'Capaian Adopsi TTE per SKPD')
@section('breadcrumb', 'Capaian Adopsi SKPD')

@section('content')
<div class="panel-header">
    <div>
        <h1 class="panel-title">Analitik Capaian Adopsi TTE per Unit Kerja (SKPD)</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Persentase capaian adopsi TTE terhadap total pegawai ASN dari seluruh SKPD aktif di lingkungan Pemerintah Kota Cirebon.
        </p>
    </div>

    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <a href="{{ route('admin.capaian-skpd.export', request()->query()) }}" class="btn btn-primary btn-sm" id="btn-export-capaian">
            &#x1F4E5; Ekspor CSV / Excel
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm">
            &larr; Kembali ke Dashboard
        </a>
    </div>
</div>

<!-- ── KPI RINGKASAN AGREGAT KOTA CIREBON ───────────────────── -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
    <!-- Rata-rata Capaian Kota -->
    <div class="card-flat" style="padding: 1rem;">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; margin-bottom: 0.35rem;">
            Rata-rata Capaian Kota
        </div>
        @php
            $rataCapaian = $kpiKota['rata_capaian'] ?? 0;
            if ($rataCapaian >= 80) {
                $kpiColor = 'var(--success, #16a34a)';
                $kpiBg = 'rgba(22, 163, 74, 0.12)';
            } elseif ($rataCapaian >= 50) {
                $kpiColor = 'var(--warning, #d97706)';
                $kpiBg = 'rgba(217, 119, 6, 0.12)';
            } else {
                $kpiColor = 'var(--danger, #dc2626)';
                $kpiBg = 'rgba(220, 38, 38, 0.12)';
            }
        @endphp
        <div class="font-mono" style="font-size: 1.75rem; font-weight: 800; color: {{ $kpiColor }};">
            {{ number_format($rataCapaian, 1) }}%
        </div>
        <div style="height: 6px; background: {{ $kpiBg }}; border-radius: 3px; overflow: hidden; margin-top: 0.5rem;">
            <div style="width: {{ min($rataCapaian, 100) }}%; height: 100%; background: {{ $kpiColor }}; border-radius: 3px; transition: width 0.6s ease;"></div>
        </div>
    </div>

    <!-- Total SKPD Aktif -->
    <div class="card-flat" style="padding: 1rem;">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; margin-bottom: 0.35rem;">
            Total SKPD Aktif
        </div>
        <div class="font-mono" style="font-size: 1.75rem; font-weight: 800; color: var(--ink-primary);">
            {{ number_format($kpiKota['total_skpd'] ?? 0) }}
        </div>
        <div style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 0.25rem;">Unit Kerja</div>
    </div>

    <!-- Total ASN se-Kota -->
    <div class="card-flat" style="padding: 1rem;">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; margin-bottom: 0.35rem;">
            Total ASN Terdata
        </div>
        <div class="font-mono" style="font-size: 1.75rem; font-weight: 800; color: var(--ink-primary);">
            {{ number_format($kpiKota['total_asn_kota'] ?? 0) }}
        </div>
        <div style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 0.25rem;">Pegawai</div>
    </div>

    <!-- Total Pemohon TTE -->
    <div class="card-flat" style="padding: 1rem;">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; margin-bottom: 0.35rem;">
            Total Pemohon TTE
        </div>
        <div class="font-mono" style="font-size: 1.75rem; font-weight: 800; color: var(--primary, #0f62fe);">
            {{ number_format($kpiKota['total_pemohon'] ?? 0) }}
        </div>
        <div style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 0.25rem;">Permohonan Masuk</div>
    </div>
</div>

<!-- ── DISTRIBUSI KATEGORI KEPATUHAN (Klik untuk Filter Cepat) ── -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
    <a href="{{ route('admin.capaian-skpd.index', array_merge(request()->except('kategori', 'page'), ['kategori' => 'optimal'])) }}"
       class="card-flat" style="padding: 0.75rem 1rem; text-decoration: none; cursor: pointer; border-left: 4px solid var(--success, #16a34a); {{ $kategori === 'optimal' ? 'background: rgba(22, 163, 74, 0.08);' : '' }}">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
            Optimal (&ge; 80%)
        </div>
        <div class="font-mono" style="font-size: 1.25rem; font-weight: 800; color: var(--success, #16a34a);">
            {{ $kpiKota['optimal'] ?? 0 }} <span style="font-size: 0.75rem; font-weight: 500;">SKPD</span>
        </div>
    </a>

    <a href="{{ route('admin.capaian-skpd.index', array_merge(request()->except('kategori', 'page'), ['kategori' => 'progres'])) }}"
       class="card-flat" style="padding: 0.75rem 1rem; text-decoration: none; cursor: pointer; border-left: 4px solid var(--warning, #d97706); {{ $kategori === 'progres' ? 'background: rgba(217, 119, 6, 0.08);' : '' }}">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
            Progres (50% &ndash; 79%)
        </div>
        <div class="font-mono" style="font-size: 1.25rem; font-weight: 800; color: var(--warning, #d97706);">
            {{ $kpiKota['progres'] ?? 0 }} <span style="font-size: 0.75rem; font-weight: 500;">SKPD</span>
        </div>
    </a>

    <a href="{{ route('admin.capaian-skpd.index', array_merge(request()->except('kategori', 'page'), ['kategori' => 'percepatan'])) }}"
       class="card-flat" style="padding: 0.75rem 1rem; text-decoration: none; cursor: pointer; border-left: 4px solid var(--danger, #dc2626); {{ $kategori === 'percepatan' ? 'background: rgba(220, 38, 38, 0.08);' : '' }}">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
            Perlu Percepatan (&lt; 50%)
        </div>
        <div class="font-mono" style="font-size: 1.25rem; font-weight: 800; color: var(--danger, #dc2626);">
            {{ $kpiKota['percepatan'] ?? 0 }} <span style="font-size: 0.75rem; font-weight: 500;">SKPD</span>
        </div>
    </a>

    <a href="{{ route('admin.capaian-skpd.index', array_merge(request()->except('kategori', 'page'), ['kategori' => 'belum_diset'])) }}"
       class="card-flat" style="padding: 0.75rem 1rem; text-decoration: none; cursor: pointer; border-left: 4px solid var(--ink-muted, #94a3b8); {{ $kategori === 'belum_diset' ? 'background: rgba(148, 163, 184, 0.08);' : '' }}">
        <div style="font-size: 0.6875rem; color: var(--ink-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
            Belum Diset Target
        </div>
        <div class="font-mono" style="font-size: 1.25rem; font-weight: 800; color: var(--ink-muted, #94a3b8);">
            {{ $kpiKota['belum_diset'] ?? 0 }} <span style="font-size: 0.75rem; font-weight: 500;">SKPD</span>
        </div>
    </a>
</div>

<!-- ── TOOLBAR PENCARIAN & FILTER ─────────────────────────────── -->
<div class="card-flat" style="padding: 0.75rem 1rem; margin-bottom: 1rem;">
    <form action="{{ route('admin.capaian-skpd.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
        <input type="text" name="q" class="form-control" style="max-width: 260px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;" placeholder="Cari nama atau kode SKPD..." value="{{ $search }}">

        <select name="kategori" class="form-select" style="max-width: 200px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;">
            <option value="semua" {{ ($kategori ?? 'semua') === 'semua' ? 'selected' : '' }}>Semua Kategori</option>
            <option value="optimal" {{ $kategori === 'optimal' ? 'selected' : '' }}>Optimal (&ge; 80%)</option>
            <option value="progres" {{ $kategori === 'progres' ? 'selected' : '' }}>Progres (50% &ndash; 79%)</option>
            <option value="percepatan" {{ $kategori === 'percepatan' ? 'selected' : '' }}>Perlu Percepatan (&lt; 50%)</option>
            <option value="belum_diset" {{ $kategori === 'belum_diset' ? 'selected' : '' }}>Belum Diset Target</option>
        </select>

        <select name="sort" class="form-select" style="max-width: 220px; font-size: 0.8125rem; padding: 0.4rem 0.6rem;">
            <option value="persentase_desc" {{ $sortMode === 'persentase_desc' ? 'selected' : '' }}>Capaian Tertinggi</option>
            <option value="persentase_asc" {{ $sortMode === 'persentase_asc' ? 'selected' : '' }}>Capaian Terendah</option>
            <option value="volume_desc" {{ $sortMode === 'volume_desc' ? 'selected' : '' }}>Volume Terbanyak</option>
            <option value="volume_asc" {{ $sortMode === 'volume_asc' ? 'selected' : '' }}>Volume Tersedikit</option>
            <option value="total_asn_desc" {{ $sortMode === 'total_asn_desc' ? 'selected' : '' }}>Total ASN Terbesar</option>
            <option value="nama_skpd_asc" {{ $sortMode === 'nama_skpd_asc' ? 'selected' : '' }}>Nama A-Z</option>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>

        @if($search || ($kategori && $kategori !== 'semua') || $sortMode !== 'persentase_desc')
            <a href="{{ route('admin.capaian-skpd.index') }}" class="btn btn-ghost btn-sm" style="font-size: 0.75rem;">
                &#x21BB; Reset Filter
            </a>
        @endif
    </form>
</div>

<!-- ── TABEL DATA SELURUH SKPD ────────────────────────────────── -->
<div class="card-flat">
    <div class="panel-header" style="padding: 0.75rem 1rem;">
        <div style="font-size: 0.8125rem; color: var(--ink-secondary);">
            Menampilkan <strong>{{ $skpdData->firstItem() ?? 0 }}&ndash;{{ $skpdData->lastItem() ?? 0 }}</strong> dari <strong>{{ $skpdData->total() }}</strong> SKPD
            @if($kategori && $kategori !== 'semua')
                &middot; Filter: <span class="badge" style="font-size: 0.65rem;">{{ ucfirst(str_replace('_', ' ', $kategori)) }}</span>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="table-capaian-skpd">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">#</th>
                    <th style="width: 80px;">Kode</th>
                    <th>Nama Unit Kerja (SKPD)</th>
                    <th style="text-align: right; width: 90px;">Total ASN</th>
                    <th style="text-align: right; width: 100px;">Pendaftar</th>
                    <th style="text-align: right; width: 90px;">Disetujui</th>
                    <th style="text-align: right; width: 80px;">Antrian</th>
                    <th style="text-align: right; width: 80px;">Ditolak</th>
                    <th style="width: 170px;">Capaian Adopsi (%)</th>
                    <th style="width: 110px;">Kategori</th>
                    <th style="width: 140px; text-align: center;">Sub-Kategori ASN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($skpdData as $index => $skpd)
                    @php
                        $persen = (float) ($skpd->persentase_capaian ?? 0);
                        $totalAsn = (int) ($skpd->total_asn ?? 0);

                        if ($totalAsn <= 0) {
                            $barColor = 'var(--ink-muted, #94a3b8)';
                            $barBg = 'rgba(148, 163, 184, 0.08)';
                            $textColor = 'var(--ink-muted, #94a3b8)';
                            $badgeClass = '';
                            $badgeStyle = 'background: #e2e8f0; color: #64748b;';
                            $badgeLabel = 'Belum Diset';
                        } elseif ($persen >= 80) {
                            $barColor = 'var(--success, #16a34a)';
                            $barBg = 'rgba(22, 163, 74, 0.12)';
                            $textColor = 'var(--success-text, #16a34a)';
                            $badgeClass = 'badge-disetujui';
                            $badgeStyle = '';
                            $badgeLabel = 'Optimal';
                        } elseif ($persen >= 50) {
                            $barColor = 'var(--warning, #d97706)';
                            $barBg = 'rgba(217, 119, 6, 0.12)';
                            $textColor = 'var(--warning-text, #d97706)';
                            $badgeClass = 'badge-warning-h3';
                            $badgeStyle = '';
                            $badgeLabel = 'Progres';
                        } else {
                            $barColor = 'var(--danger, #dc2626)';
                            $barBg = 'rgba(220, 38, 38, 0.12)';
                            $textColor = 'var(--danger, #dc2626)';
                            $badgeClass = 'badge-ditolak';
                            $badgeStyle = '';
                            $badgeLabel = 'Percepatan';
                        }
                        $barWidth = min($persen, 100);
                        $rowRank = ($skpdData->currentPage() - 1) * $skpdData->perPage() + $index + 1;
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: var(--ink-muted);">{{ $rowRank }}</td>
                        <td class="font-mono" style="font-weight: 600; font-size: 0.75rem;">{{ $skpd->kode_skpd }}</td>
                        <td style="font-weight: 600;">{{ $skpd->nama_skpd }}</td>
                        <td style="text-align: right;" class="font-mono">
                            @if($totalAsn > 0)
                                {{ number_format($totalAsn) }}
                            @else
                                <span style="color: var(--ink-muted); font-style: italic; font-size: 0.7rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right;" class="font-mono"><strong>{{ number_format($skpd->total_pendaftar) }}</strong></td>
                        <td style="text-align: right; color: var(--success-text);" class="font-mono">{{ number_format($skpd->total_disetujui) }}</td>
                        <td style="text-align: right; color: var(--primary);" class="font-mono">{{ number_format($skpd->total_antrian) }}</td>
                        <td style="text-align: right; color: var(--danger);" class="font-mono">{{ number_format($skpd->total_ditolak) }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="flex: 1; height: 8px; background: {{ $barBg }}; border-radius: 4px; overflow: hidden;">
                                    <div style="width: {{ $barWidth }}%; height: 100%; background: {{ $barColor }}; border-radius: 4px; transition: width 0.6s ease;"></div>
                                </div>
                                <span class="font-mono" style="font-size: 0.75rem; font-weight: 700; color: {{ $textColor }}; min-width: 45px; text-align: right;">
                                    {{ number_format($persen, 1) }}%
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}" style="{{ $badgeStyle }}">{{ $badgeLabel }}</span>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="breakdown-btn-toggle" onclick="toggleSkpdBreakdown('{{ $skpd->uuid }}', this)" id="btn-toggle-{{ $skpd->uuid }}">
                                <span>&#x1F4CA; Sub-Unit & Hierarki</span>
                            </button>
                        </td>
                    </tr>
                    <!-- Row Sub-Breakdown Dinamis -->
                    <tr id="breakdown-row-{{ $skpd->uuid }}" class="breakdown-row" style="display: none;">
                        <td colspan="11">
                            <div id="breakdown-container-{{ $skpd->uuid }}" class="breakdown-container">
                                <div style="text-align: center; padding: 1.5rem; color: var(--ink-muted);">
                                    <span class="inline-block animate-spin" style="margin-right: 0.5rem;">&#x21BB;</span> Memuat breakdown hierarki & sub-kategori...
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; color: var(--ink-muted); padding: 2.5rem;">
                            @if($search || ($kategori && $kategori !== 'semua'))
                                Tidak ada SKPD yang cocok dengan filter pencarian Anda.
                            @else
                                Belum ada data SKPD yang tercatat.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Legenda Indikator Capaian -->
    <div style="padding: 0.75rem 1rem; border-top: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: center;">
        <span style="font-size: 0.7rem; font-weight: 600; color: var(--ink-muted); text-transform: uppercase; letter-spacing: 0.04em;">Legenda:</span>
        <span style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;">
            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: var(--success, #16a34a);"></span>
            &ge; 80% (Optimal)
        </span>
        <span style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;">
            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: var(--warning, #d97706);"></span>
            50% &ndash; 79% (Progres)
        </span>
        <span style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;">
            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: var(--danger, #dc2626);"></span>
            &lt; 50% (Perlu Percepatan)
        </span>
        <span style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem;">
            <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: var(--ink-muted, #94a3b8);"></span>
            Belum Diset
        </span>
    </div>

    <!-- Paginasi -->
    <div style="padding: 0.75rem 1rem; border-top: 1px solid var(--border);">
        {{ $skpdData->links('vendor.pagination.carbon') }}
    </div>
</div>
@endsection

@push('styles')
<style>
    .breakdown-row {
        background: #f8f9fa;
    }
    .breakdown-container {
        padding: 1.25rem 1.5rem;
        background: #fbfcfd;
        border-top: 1px solid var(--border);
        border-bottom: 2px solid var(--primary, #0f62fe);
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .breakdown-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.25rem;
        padding-bottom: 0.875rem;
        border-bottom: 1px solid var(--border);
        gap: 1rem;
        flex-wrap: wrap;
    }
    .breakdown-title {
        font-size: 1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .breakdown-btn-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 600;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 0;
        cursor: pointer;
        transition: all 0.15s ease;
        color: var(--ink-primary);
    }
    .breakdown-btn-toggle:hover {
        background: #f0f4f8;
        border-color: var(--primary);
        color: var(--primary);
    }
    .breakdown-btn-toggle.is-active {
        background: var(--primary, #0f62fe);
        border-color: var(--primary, #0f62fe);
        color: #fff;
    }
    .breakdown-section-title {
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ink-secondary);
        margin-bottom: 0.65rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .breakdown-macro-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .breakdown-macro-card {
        background: #fff;
        border: 1px solid var(--border);
        border-left: 4px solid var(--primary);
        padding: 0.75rem 1rem;
        transition: all 0.15s ease;
        cursor: pointer;
        position: relative;
    }
    .breakdown-macro-card:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .breakdown-macro-card.is-active-macro {
        background: #f0f7ff;
        border-color: var(--primary);
        box-shadow: 0 0 0 1px var(--primary);
    }
    .breakdown-subtable-box {
        background: #fff;
        border: 1px solid var(--border);
        padding: 0.875rem 1rem;
        margin-bottom: 1.25rem;
    }
    .breakdown-subtable-title {
        font-size: 0.8125rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border);
    }
    .breakdown-metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .breakdown-stat-card {
        background: #fff;
        border: 1px solid var(--border);
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
    }
    .breakdown-stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ink-muted);
    }
    .breakdown-stat-val {
        font-size: 1.375rem;
        font-weight: 800;
        font-family: 'IBM Plex Mono', monospace;
        margin: 2px 0;
    }
    .breakdown-subtables-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .btn-filter-sub {
        font-size: 0.6875rem;
        padding: 0.2rem 0.5rem;
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 0;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.15s ease;
    }
    .btn-filter-sub:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .btn-filter-sub.is-active-btn {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .sub-filter-active-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: #e8f4fd;
        border: 1px solid #78a9ff;
        margin-bottom: 0.75rem;
        font-size: 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script>
    const loadedBreakdowns = {};

    function toggleSkpdBreakdown(uuid, btn) {
        const row = document.getElementById('breakdown-row-' + uuid);
        const container = document.getElementById('breakdown-container-' + uuid);
        if (!row || !container) return;

        const isCurrentlyOpen = row.style.display !== 'none';

        if (isCurrentlyOpen) {
            row.style.display = 'none';
            btn.classList.remove('is-active');
            btn.innerHTML = '<span>&#x1F4CA; Sub-Unit & Hierarki</span>';
            return;
        }

        row.style.display = 'table-row';
        btn.classList.add('is-active');
        btn.innerHTML = '<span>✕ Tutup</span>';

        if (loadedBreakdowns[uuid]) {
            renderBreakdownContent(container, loadedBreakdowns[uuid]);
            return;
        }

        fetch('/admin/capaian-skpd/' + uuid + '/breakdown', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Gagal memuat data breakdown');
            return res.json();
        })
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Data tidak tersedia');
            loadedBreakdowns[uuid] = res;
            renderBreakdownContent(container, res);
        })
        .catch(err => {
            console.error('Error breakdown:', err);
            container.innerHTML = `
                <div style="padding: 1.5rem; text-align: center; color: var(--danger);">
                    <div>Gagal memuat rincian breakdown hierarki & sub-kategori untuk SKPD ini.</div>
                    <button type="button" class="btn btn-outline btn-sm" style="margin-top: 0.5rem;" onclick="loadedBreakdowns['${uuid}']=null; toggleSkpdBreakdown('${uuid}', document.getElementById('btn-toggle-${uuid}'))">
                        &#x21BB; Coba Lagi
                    </button>
                </div>
            `;
        });
    }

    function filterSubUnitBreakdown(uuid, subKategori, subUnit) {
        const container = document.getElementById('breakdown-container-' + uuid);
        if (!container) return;

        const decodedSubUnit = subUnit ? decodeURIComponent(subUnit) : '';
        const queryParams = new URLSearchParams();
        if (subKategori) queryParams.set('sub_kategori', subKategori);
        if (decodedSubUnit) queryParams.set('sub_unit', decodedSubUnit);

        fetch(`/admin/capaian-skpd/${uuid}/breakdown?${queryParams.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Gagal memuat data filter');
            return res.json();
        })
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Data tidak tersedia');
            renderBreakdownContent(container, res, subKategori, decodedSubUnit);
        })
        .catch(err => {
            console.error('Error filter breakdown:', err);
        });
    }

    function renderBreakdownContent(container, data, activeSubKategori = null, activeSubUnit = null) {
        const skpd = data.skpd;
        const hierarki = data.hierarki || {};
        const breakdownJabatan = data.breakdown_jabatan || [];
        const breakdownGolongan = data.breakdown_golongan || [];
        const breakdownSubKategori = data.breakdown_sub_kategori || [];
        const kategoriMakro = data.kategori_makro || [];
        const pemohonTerbaru = data.pemohon_terbaru || [];

        // 1. Kartu Ringkasan Kategori Makro ASN
        let macroCards = '';
        if (kategoriMakro.length > 0) {
            kategoriMakro.forEach(m => {
                const isSelected = activeSubKategori === m.kategori && !activeSubUnit;
                const color = m.badge?.color || '#0f62fe';
                const label = m.badge?.label || m.kategori;
                const unitCount = m.sub_units ? m.sub_units.length : 0;

                macroCards += `
                    <div class="breakdown-macro-card ${isSelected ? 'is-active-macro' : ''}" style="border-left-color: ${color};" onclick="filterSubUnitBreakdown('${skpd.uuid}', '${isSelected ? '' : m.kategori}', '')" title="Klik untuk filter data pemohon">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-weight: 700; font-size: 0.8125rem; color: ${color};">${label}</span>
                            <span style="font-size: 0.6875rem; color: var(--ink-muted); font-family: monospace;">${unitCount} Unit</span>
                        </div>
                        <div class="font-mono" style="font-size: 1.375rem; font-weight: 800; color: var(--ink-primary);">
                            ${Number(m.total_pendaftar).toLocaleString('id-ID')} <span style="font-size: 0.6875rem; font-weight: 500; color: var(--ink-muted);">Pemohon</span>
                        </div>
                        <div style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 4px; display: flex; gap: 0.5rem;">
                            <span>Terbit: <strong style="color: var(--success-text);">${m.total_disetujui}</strong></span>
                            <span>Antri: <strong style="color: var(--primary);">${m.total_antrian}</strong></span>
                            <span>Tolak: <strong style="color: var(--danger);">${m.total_ditolak}</strong></span>
                        </div>
                        <div style="margin-top: 6px; font-size: 0.6875rem; color: ${isSelected ? 'var(--primary)' : 'var(--ink-muted)'}; font-weight: 600;">
                            ${isSelected ? '✓ Filter Aktif (Klik untuk batal)' : '🔍 Drill-Down Pemohon →'}
                        </div>
                    </div>
                `;
            });
        }

        // 2. Baris Tabel Sub-Unit & Sub-Kategori
        let subKategoriRows = '';
        if (breakdownSubKategori.length > 0) {
            breakdownSubKategori.forEach(item => {
                const isCurrentUnit = activeSubUnit === item.sub_unit_kerja;
                const color = item.badge?.color || '#0f62fe';
                const badgeLabel = item.badge?.label || item.sub_kategori_asn;
                const persentase = item.persentase_capaian || 0;

                let barColor = 'var(--danger, #dc2626)';
                if (persentase >= 80) barColor = 'var(--success, #16a34a)';
                else if (persentase >= 50) barColor = 'var(--warning, #d97706)';

                const targetText = item.target_asn && item.target_asn > 0
                    ? `${Number(item.target_asn).toLocaleString('id-ID')} ASN`
                    : `<span style="color: var(--ink-muted); font-size: 0.7rem; font-style: italic;">Basis pemohon (${item.total_pendaftar})</span>`;

                subKategoriRows += `
                    <tr style="${isCurrentUnit ? 'background: #edf5ff; font-weight: 600;' : ''}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="badge" style="background: ${color}15; color: ${color}; border: 1px solid ${color}40; font-size: 0.6875rem;">
                                    ${badgeLabel}
                                </span>
                                <strong style="color: var(--ink-primary);">${item.sub_unit_kerja}</strong>
                            </div>
                        </td>
                        <td class="font-mono" style="text-align: right; font-size: 0.75rem;">${targetText}</td>
                        <td class="font-mono" style="text-align: right; font-weight: 700;">${Number(item.total_pendaftar).toLocaleString('id-ID')}</td>
                        <td class="font-mono" style="text-align: right; color: var(--success-text);">${Number(item.total_disetujui).toLocaleString('id-ID')}</td>
                        <td class="font-mono" style="text-align: right; color: var(--primary);">${Number(item.total_antrian).toLocaleString('id-ID')}</td>
                        <td class="font-mono" style="text-align: right; color: var(--danger);">${Number(item.total_ditolak).toLocaleString('id-ID')}</td>
                        <td style="width: 140px;">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <div style="flex: 1; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                                    <div style="width: ${Math.min(persentase, 100)}%; height: 100%; background: ${barColor};"></div>
                                </div>
                                <span class="font-mono" style="font-size: 0.6875rem; font-weight: 700; min-width: 40px; text-align: right; color: ${barColor};">
                                    ${persentase}%
                                </span>
                            </div>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button" class="btn-filter-sub ${isCurrentUnit ? 'is-active-btn' : ''}" onclick="filterSubUnitBreakdown('${skpd.uuid}', '${item.sub_kategori_asn}', '${encodeURIComponent(item.sub_unit_kerja)}')">
                                ${isCurrentUnit ? '✓ Sedang Dilihat' : '🔍 Lihat ASN'}
                            </button>
                            <a href="/admin/capaian-skpd/${skpd.uuid}/export-breakdown?sub_unit=${encodeURIComponent(item.sub_unit_kerja)}" class="btn-filter-sub" style="text-decoration: none; display: inline-block; margin-left: 2px;" title="Unduh CSV Sub-Unit Ini">
                                &#x1F4E5;
                            </a>
                        </td>
                    </tr>
                `;
            });
        } else {
            subKategoriRows = '<tr><td colspan="8" style="text-align: center; color: var(--ink-muted); padding: 1.25rem;">Belum ada data sub-kategori pendaftar di SKPD ini.</td></tr>';
        }

        // 3. Baris Jabatan & Golongan
        let jabatanRows = '';
        if (breakdownJabatan.length > 0) {
            breakdownJabatan.forEach(item => {
                jabatanRows += `
                    <tr>
                        <td style="font-weight: 600;">${item.nama_jabatan}</td>
                        <td class="font-mono" style="text-align: right; font-weight: 700;">${item.total}</td>
                        <td class="font-mono" style="text-align: right; color: var(--success-text);">${item.disetujui}</td>
                        <td class="font-mono" style="text-align: right; color: var(--primary);">${item.antrian}</td>
                    </tr>
                `;
            });
        } else {
            jabatanRows = '<tr><td colspan="4" style="text-align: center; color: var(--ink-muted); padding: 1rem;">Belum ada pemohon terdaftar di SKPD ini.</td></tr>';
        }

        let golonganRows = '';
        if (breakdownGolongan.length > 0) {
            breakdownGolongan.forEach(item => {
                golonganRows += `
                    <tr>
                        <td class="font-mono" style="font-weight: 700;">${item.kode_golongan}</td>
                        <td>${item.nama_pangkat}</td>
                        <td class="font-mono" style="text-align: right; font-weight: 700;">${item.total}</td>
                        <td class="font-mono" style="text-align: right; color: var(--success-text);">${item.disetujui}</td>
                    </tr>
                `;
            });
        } else {
            golonganRows = '<tr><td colspan="4" style="text-align: center; color: var(--ink-muted); padding: 1rem;">Belum ada data golongan terdaftar.</td></tr>';
        }

        // 4. Banner Filter Aktif
        let filterBanner = '';
        if (activeSubUnit || activeSubKategori) {
            const filterText = activeSubUnit
                ? `Sub-Unit Kerja: <strong>${activeSubUnit}</strong> (${activeSubKategori})`
                : `Sub-Kategori ASN: <strong>${activeSubKategori}</strong>`;

            filterBanner = `
                <div class="sub-filter-active-bar">
                    <div>
                        <span>🔍 Filter Aktif Pemohon:</span> ${filterText}
                        <span style="color: var(--ink-muted); margin-left: 0.5rem;">(Menampilkan ${pemohonTerbaru.length} ASN)</span>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" style="font-size: 0.6875rem; padding: 0.15rem 0.5rem;" onclick="filterSubUnitBreakdown('${skpd.uuid}', '', '')">
                        ✕ Hapus Filter (Tampilkan Semua ASN)
                    </button>
                </div>
            `;
        }

        // 5. Baris Tabel Pemohon Terkini
        let pemohonRows = '';
        if (pemohonTerbaru.length > 0) {
            pemohonTerbaru.forEach(p => {
                let badgeClass = 'badge-menunggu';
                if (p.status === 'DISETUJUI') badgeClass = 'badge-disetujui';
                else if (p.status === 'DITOLAK') badgeClass = 'badge-ditolak';

                pemohonRows += `
                    <tr>
                        <td class="font-mono" style="font-weight: 600; color: var(--primary);">${p.nomor_referensi}</td>
                        <td>
                            <div style="font-weight: 600;">${p.nama_lengkap}</div>
                            <div style="font-size: 0.6875rem; color: var(--ink-muted);" class="font-mono">NIP: ${p.nip}</div>
                        </td>
                        <td>
                            <span class="badge" style="font-size: 0.65rem; background: #e0e0e0; color: #161616;">
                                ${p.sub_kategori_asn || 'DINAS_INDUK'}
                            </span>
                        </td>
                        <td style="font-size: 0.75rem; font-weight: 500;">
                            ${p.sub_unit_kerja || 'Kantor Induk'}
                        </td>
                        <td>${p.jabatan}</td>
                        <td class="font-mono">${p.golongan}</td>
                        <td><span class="badge ${badgeClass}">${p.status}</span></td>
                        <td class="font-mono" style="font-size: 0.75rem;">${p.tanggal_daftar}</td>
                    </tr>
                `;
            });
        } else {
            pemohonRows = '<tr><td colspan="8" style="text-align: center; color: var(--ink-muted); padding: 1.5rem;">Tidak ada pemohon pada filter sub-kategori / unit ini.</td></tr>';
        }

        container.innerHTML = `
            <div class="breakdown-header">
                <div>
                    <div class="breakdown-title">
                        <span>&#x1F3DB; Breakdown Sub-Kategori & Hierarki: <strong>${skpd.nama_skpd}</strong></span>
                        <span class="badge" style="background: #e2e8f0; font-family: monospace;">${skpd.kode_skpd}</span>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--ink-muted); margin-top: 4px;">
                        Target Total ASN SKPD: <strong>${skpd.total_asn > 0 ? Number(skpd.total_asn).toLocaleString('id-ID') + ' Pegawai' : 'Belum diset'}</strong> &bull; Total Pemohon TTE Masuk: <strong>${Number(skpd.total_pendaftar).toLocaleString('id-ID')} Orang</strong> (${skpd.persentase_capaian}%)
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="/admin/capaian-skpd/${skpd.uuid}/export-breakdown" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">
                        &#x1F4E5; Unduh CSV SKPD Lengkap
                    </a>
                </div>
            </div>

            <!-- 1. Kategori Makro ASN (Dinas Kesehatan, Pendidikan, Kecamatan, dll.) -->
            <div class="breakdown-section-title">
                <span>&#x1F3F7;&#xFE0F; Kategori Penempatan ASN di bawah SKPD ini (Klik untuk Filter)</span>
            </div>
            <div class="breakdown-macro-grid">
                ${macroCards || '<div style="color: var(--ink-muted); font-size: 0.75rem;">Data kategori penempatan belum tersedia.</div>'}
            </div>

            <!-- 2. Tabel Rincian per Sub-Unit Kerja -->
            <div class="breakdown-subtable-box">
                <div class="breakdown-subtable-title">
                    <span>&#x1F4CA; Capaian Adopsi per Sub-Unit Kerja / Instansi Penempatan ASN</span>
                    <span style="font-size: 0.6875rem; font-weight: normal; color: var(--ink-muted);">Dinamis Seluruh SKPD Kota Cirebon</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table" style="font-size: 0.75rem;">
                        <thead>
                            <tr>
                                <th>Sub-Kategori / Unit Penempatan</th>
                                <th style="text-align: right; width: 100px;">Target ASN</th>
                                <th style="text-align: right; width: 80px;">Pemohon</th>
                                <th style="text-align: right; width: 70px;">Terbit</th>
                                <th style="text-align: right; width: 70px;">Antri</th>
                                <th style="text-align: right; width: 70px;">Ditolak</th>
                                <th style="width: 140px;">Capaian (%)</th>
                                <th style="width: 140px; text-align: right;">Aksi Rincian</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${subKategoriRows}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Metrik Hierarki Organisasi -->
            <div class="breakdown-section-title">
                <span>&#x1F4BC; Klasifikasi Jabatan & Penetrasi TTE</span>
            </div>
            <div class="breakdown-metric-grid">
                <div class="breakdown-stat-card" style="border-left: 4px solid #0f62fe;">
                    <span class="breakdown-stat-label">Pimpinan & Struktural</span>
                    <span class="breakdown-stat-val" style="color: #0f62fe;">${hierarki.struktural?.total || 0}</span>
                    <span style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 2px;">
                        Disetujui: <strong>${hierarki.struktural?.disetujui || 0}</strong> &bull; Antri: <strong>${hierarki.struktural?.antrian || 0}</strong>
                    </span>
                </div>
                <div class="breakdown-stat-card" style="border-left: 4px solid #8a3ffc;">
                    <span class="breakdown-stat-label">Fungsional Tertentu</span>
                    <span class="breakdown-stat-val" style="color: #8a3ffc;">${hierarki.fungsional?.total || 0}</span>
                    <span style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 2px;">
                        Disetujui: <strong>${hierarki.fungsional?.disetujui || 0}</strong> &bull; Antri: <strong>${hierarki.fungsional?.antrian || 0}</strong>
                    </span>
                </div>
                <div class="breakdown-stat-card" style="border-left: 4px solid #007d79;">
                    <span class="breakdown-stat-label">Pelaksana & Staf</span>
                    <span class="breakdown-stat-val" style="color: #007d79;">${hierarki.pelaksana?.total || 0}</span>
                    <span style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 2px;">
                        Disetujui: <strong>${hierarki.pelaksana?.disetujui || 0}</strong> &bull; Antri: <strong>${hierarki.pelaksana?.antrian || 0}</strong>
                    </span>
                </div>
                <div class="breakdown-stat-card" style="border-left: 4px solid var(--success);">
                    <span class="breakdown-stat-label">Penetrasi Terbit</span>
                    <span class="breakdown-stat-val" style="color: var(--success);">${skpd.persentase_capaian}%</span>
                    <span style="font-size: 0.6875rem; color: var(--ink-muted); margin-top: 2px;">
                        ${skpd.total_disetujui} terbit dari ${skpd.total_pendaftar} diajukan
                    </span>
                </div>
            </div>

            <!-- 4. Subtabel Distribusi Jabatan & Golongan -->
            <div class="breakdown-subtables-grid">
                <div class="breakdown-subtable-box" style="margin-bottom: 0;">
                    <div class="breakdown-subtable-title">
                        <span>&#x1F4BC; Distribusi Formasi Jabatan</span>
                    </div>
                    <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                        <table class="data-table" style="font-size: 0.75rem;">
                            <thead>
                                <tr>
                                    <th>Jabatan</th>
                                    <th style="text-align: right; width: 60px;">Total</th>
                                    <th style="text-align: right; width: 60px;">Terbit</th>
                                    <th style="text-align: right; width: 60px;">Antri</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${jabatanRows}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="breakdown-subtable-box" style="margin-bottom: 0;">
                    <div class="breakdown-subtable-title">
                        <span>&#x1F396; Distribusi Berdasarkan Golongan ASN</span>
                    </div>
                    <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                        <table class="data-table" style="font-size: 0.75rem;">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Gol</th>
                                    <th>Pangkat ASN</th>
                                    <th style="text-align: right; width: 60px;">Total</th>
                                    <th style="text-align: right; width: 60px;">Terbit</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${golonganRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 5. Tabel Daftar Pemohon ASN (Mendukung Filter Sub-Kategori / Sub-Unit) -->
            <div class="breakdown-subtable-box" style="margin-top: 1rem; margin-bottom: 0;">
                <div class="breakdown-subtable-title">
                    <span>&#x1F465; Daftar Pemohon Terkini pada Unit / Sub-Unit Terpilih</span>
                    @if(request()->is('admin*'))
                        <span style="font-size: 0.6875rem; color: var(--ink-muted);">Maksimal 15 pendaftar terkini</span>
                    @endif
                </div>

                ${filterBanner}

                <div class="table-responsive">
                    <table class="data-table" style="font-size: 0.75rem;">
                        <thead>
                            <tr>
                                <th>No. Referensi</th>
                                <th>Nama ASN</th>
                                <th>Sub-Kategori</th>
                                <th>Sub-Unit Penempatan</th>
                                <th>Jabatan</th>
                                <th>Gol</th>
                                <th>Status</th>
                                <th>Tgl Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pemohonRows}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
</script>
@endpush
