@extends('layouts.admin')

@section('title', 'Dashboard Transaksi & Pemantauan TTE')
@section('breadcrumb', 'Dashboard')

@section('content')
<!-- Header & Aksi Sinkronisasi Cepat -->
<div class="panel-header">
    <div>
        <h1 class="panel-title">Dashboard Pemantauan TTE Kota Cirebon</h1>
        <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-top: 2px;">
            Metrik agregasi transaksi sub-milidetik disinkronkan dari Redis Cache (Terakhir: {{ \Carbon\Carbon::parse($summary['last_updated_at'] ?? now())->format('H:i:s') }} WIB).
        </p>
    </div>

    <button type="button" class="btn btn-outline btn-sm" id="btn-refresh-stats" onclick="refreshDashboardCache()">
        ↻ Perbarui Cache Statistik
    </button>
</div>

<!-- ── 4 STAT CARDS UTAMA ────────────────────────────────────── -->
<div class="grid-stats">
    <!-- Card 1: Total ASN -->
    <div class="stat-card">
        <div class="stat-card-label">Total ASN Terdaftar</div>
        <div class="stat-card-value font-mono" id="stat-total-asn">{{ number_format($summary['total_asn'] ?? 0) }}</div>
        <div class="stat-card-footer">Seluruh permohonan masuk</div>
    </div>

    <!-- Card 2: Sertifikat Terbit Aktif -->
    <div class="stat-card card-success">
        <div class="stat-card-label">Sertifikat TTE Aktif</div>
        <div class="stat-card-value font-mono" id="stat-total-terbit" style="color: var(--success-text);">
            {{ number_format($summary['total_terbit'] ?? 0) }}
        </div>
        <div class="stat-card-footer">Masa berlaku 2 tahun</div>
    </div>

    <!-- Card 3: Antrian Verifikasi -->
    <div class="stat-card">
        <div class="stat-card-label">Antrian Verifikasi</div>
        <div class="stat-card-value font-mono" id="stat-total-antrian" style="color: var(--primary);">
            {{ number_format($summary['total_antrian'] ?? 0) }}
        </div>
        <div class="stat-card-footer">Menunggu audit Tahap 1</div>
    </div>

    <!-- Card 4: Peringatan H-7 Kedaluwarsa -->
    <div class="stat-card card-warning">
        <div class="stat-card-label">Peringatan Kadaluarsa (H-7)</div>
        <div class="stat-card-value font-mono" id="stat-total-warning" style="color: var(--warning-text);">
            {{ number_format($summary['total_warning_h7'] ?? $summary['total_warning_h3'] ?? 0) }}
        </div>
        <div class="stat-card-footer">Perlu perpanjangan segera</div>
    </div>
</div>

<!-- ── DUA KOLOM: ANTRIAN TERBARU & ZONA PERINGATAN H-7 ───────── -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(440px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Widget 1: Antrian Verifikasi FIFO Terdepan -->
    <div class="card-flat" style="margin-bottom: 0;">
        <div class="panel-header" style="flex-wrap: wrap; gap: 0.75rem; align-items: flex-start;">
            <div>
                <h2 style="font-size: 1rem; font-weight: 600;">Antrian Verifikasi Terdepan (FIFO)</h2>
                <div style="font-size: 0.75rem; color: var(--ink-muted);">Permohonan paling awal yang menunggu persetujuan (Verifikasi Pertama)</div>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <!-- Fitur Pencarian Cepat Berdasarkan Deskripsi / Identitas ASN -->
                <form action="{{ route('admin.dashboard') }}" method="GET" style="display: flex; gap: 0.35rem; align-items: center;">
                    <input type="text" name="q" class="form-control" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; width: 190px;" placeholder="Cari deskripsi / nama..." value="{{ $searchQuery ?? request('q') }}">
                    <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">🔍 Cari</button>
                    @if(!empty($searchQuery))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.5rem; color: var(--danger);" title="Reset Pencarian">✕</a>
                    @endif
                </form>
                <a href="{{ route('admin.verifikasi.index', !empty($searchQuery) ? ['q' => $searchQuery] : []) }}" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Lihat Semua Antrian →</a>
            </div>
        </div>

        @if(!empty($searchQuery))
            <div style="background: rgba(15, 98, 254, 0.05); border-left: 3px solid var(--primary); padding: 0.4rem 0.75rem; font-size: 0.75rem; color: var(--ink-secondary); margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Menampilkan hasil pencarian verifikasi untuk: <strong>{{ $searchQuery }}</strong> (mencakup deskripsi penempatan kerja ASN)</span>
                <a href="{{ route('admin.dashboard') }}" style="color: var(--danger); text-decoration: none; font-weight: 600;">Hapus filter</a>
            </div>
        @endif

        <div class="table-responsive" style="border: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Referensi</th>
                        <th>Nama ASN</th>
                        <th>SKPD</th>
                        <th>Deskripsi Penempatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAntrian as $antrian)
                        <tr>
                            <td class="font-mono" style="font-weight: 600; color: var(--primary);">
                                {{ $antrian->nomor_referensi }}
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $antrian->nama_lengkap }}</div>
                                <div style="font-size: 0.6875rem; color: var(--ink-muted);" class="font-mono">NIP: {{ $antrian->nip }}</div>
                            </td>
                            <td>{{ $antrian->skpd->nama_skpd ?? '-' }}</td>
                            <td>
                                @if(!empty($antrian->deskripsi))
                                    <span class="badge" style="background: rgba(15, 98, 254, 0.08); color: var(--primary); border: 1px solid rgba(15, 98, 254, 0.2); font-size: 0.75rem; padding: 3px 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: normal; text-align: left; max-width: 220px;">
                                        📍 {{ $antrian->deskripsi }}
                                    </span>
                                @else
                                    <span style="color: var(--ink-muted); font-size: 0.75rem; font-style: italic;">(Tidak ada deskripsi)</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.verifikasi.show', $antrian->nomor_referensi) }}" class="btn btn-primary btn-sm">
                                    Verifikasi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--ink-muted); padding: 2rem;">
                                @if(!empty($searchQuery))
                                    Tidak ada antrian verifikasi yang cocok dengan kata kunci deskripsi/nama "<strong>{{ $searchQuery }}</strong>".
                                @else
                                    Tidak ada antrian verifikasi yang tertunda saat ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Widget 2: Sertifikat Zona Peringatan H-7 -->
    <div class="card-flat" style="margin-bottom: 0; border-top: 4px solid var(--warning);">
        <div class="panel-header">
            <div>
                <h2 style="font-size: 1rem; font-weight: 600; color: var(--warning-text);">Sertifikat Mendekati Kadaluarsa (H-7)</h2>
                <div style="font-size: 0.75rem; color: var(--ink-muted);">Sertifikat ASN yang jatuh tempo dalam 7 hari ke depan</div>
            </div>
            <a href="{{ route('admin.penerbitan.index', ['status' => 'WARNING_H7']) }}" class="btn btn-outline btn-sm">Kelola →</a>
        </div>

        <div class="table-responsive" style="border: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama ASN</th>
                        <th>Email Dinas</th>
                        <th>Tgl Kadaluarsa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warningCertificates as $cert)
                        @php
                            $badge = $cert->status_masa_berlaku_badge;
                            $sisaHari = $cert->sisa_hari;
                        @endphp
                        <tr class="{{ $sisaHari !== null && $sisaHari >= 0 ? 'is-warning' : '' }}">
                            <td>
                                <div style="font-weight: 600;">{{ $cert->pendaftaran->nama_lengkap ?? '-' }}</div>
                                @if(!empty($cert->pendaftaran->nip))
                                    <div style="font-size: 0.6875rem; color: var(--ink-muted);" class="font-mono">
                                        NIP: {{ $cert->pendaftaran->nip }}
                                    </div>
                                @endif
                            </td>
                            <td class="font-mono" style="font-size: 0.75rem;">{{ $cert->email_dinas }}</td>
                            <td class="font-mono" style="font-weight: 600; color: {{ $sisaHari !== null && $sisaHari < 0 ? 'var(--danger-text)' : 'var(--ink)' }};">
                                {{ $cert->tanggal_kadaluarsa?->format('d/m/Y') }}
                            </td>
                            <td>
                                <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--ink-muted); padding: 2rem;">
                                Tidak ada sertifikat dalam zona peringatan kedaluwarsa H-7.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── DISTRIBUSI TRANSAKSI PER SKPD (HYBRID: KEPATUHAN % / VOLUME) ── -->
<div class="card-flat">
    <div class="panel-header">
        <div>
            <h2 style="font-size: 1rem; font-weight: 600;">
                @if($sortMode === 'persentase')
                    Peringkat Kepatuhan Adopsi TTE per SKPD (Top 10)
                @else
                    Distribusi Volume Permohonan TTE per SKPD (Top 10)
                @endif
            </h2>
            <div style="font-size: 0.75rem; color: var(--ink-muted);">
                @if($sortMode === 'persentase')
                    Diurutkan berdasarkan persentase capaian adopsi TTE terhadap total pegawai ASN
                @else
                    Diurutkan berdasarkan volume absolut permohonan masuk ke Diskominfo
                @endif
            </div>
        </div>

        <!-- Toggle Filter Sorting -->
        <div style="display: flex; gap: 0.375rem; align-items: center;">
            <a href="{{ route('admin.dashboard', ['sort' => 'persentase']) }}"
               class="btn btn-sm {{ $sortMode === 'persentase' ? 'btn-primary' : 'btn-outline' }}"
               style="font-size: 0.75rem; padding: 0.3rem 0.65rem;">
                &#x1F4CA; Kepatuhan (%)
            </a>
            <a href="{{ route('admin.dashboard', ['sort' => 'volume']) }}"
               class="btn btn-sm {{ $sortMode === 'volume' ? 'btn-primary' : 'btn-outline' }}"
               style="font-size: 0.75rem; padding: 0.3rem 0.65rem;">
                &#x1F4CB; Volume
            </a>
            <span style="border-left: 1px solid var(--border); height: 20px; margin: 0 0.25rem;"></span>
            <a href="{{ route('admin.capaian-skpd.index') }}"
               class="btn btn-sm btn-outline"
               style="font-size: 0.75rem; padding: 0.3rem 0.65rem;">
                Lihat Seluruh SKPD &rarr;
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">#</th>
                    <th style="width: 80px;">Kode</th>
                    <th>Nama Unit Kerja (SKPD)</th>
                    <th style="text-align: right; width: 90px;">Total ASN</th>
                    <th style="text-align: right; width: 110px;">Pendaftar</th>
                    <th style="text-align: right; width: 100px;">Disetujui</th>
                    <th style="text-align: right; width: 90px;">Antrian</th>
                    <th style="width: 200px;">Capaian Adopsi (%)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($skpdStats as $index => $skpd)
                    @php
                        $persen = (float) ($skpd->persentase_capaian ?? 0);
                        if ($persen >= 80) {
                            $barColor = 'var(--success, #16a34a)';
                            $barBg = 'rgba(22, 163, 74, 0.12)';
                            $textColor = 'var(--success-text, #16a34a)';
                        } elseif ($persen >= 50) {
                            $barColor = 'var(--warning, #d97706)';
                            $barBg = 'rgba(217, 119, 6, 0.12)';
                            $textColor = 'var(--warning-text, #d97706)';
                        } elseif ($persen > 0) {
                            $barColor = 'var(--danger, #dc2626)';
                            $barBg = 'rgba(220, 38, 38, 0.12)';
                            $textColor = 'var(--danger, #dc2626)';
                        } else {
                            $barColor = 'var(--ink-muted, #94a3b8)';
                            $barBg = 'rgba(148, 163, 184, 0.08)';
                            $textColor = 'var(--ink-muted, #94a3b8)';
                        }
                        $barWidth = min($persen, 100);
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: var(--ink-muted);">{{ $index + 1 }}</td>
                        <td class="font-mono" style="font-weight: 600; font-size: 0.75rem;">{{ $skpd->kode_skpd }}</td>
                        <td style="font-weight: 600;">{{ $skpd->nama_skpd }}</td>
                        <td style="text-align: right;" class="font-mono">
                            @if(($skpd->total_asn ?? 0) > 0)
                                {{ number_format($skpd->total_asn) }}
                            @else
                                <span style="color: var(--ink-muted); font-style: italic; font-size: 0.7rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right;" class="font-mono"><strong>{{ number_format($skpd->total_pendaftar) }}</strong></td>
                        <td style="text-align: right; color: var(--success-text);" class="font-mono">{{ number_format($skpd->total_disetujui) }}</td>
                        <td style="text-align: right; color: var(--primary);" class="font-mono">{{ number_format($skpd->total_antrian) }}</td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--ink-muted); padding: 2rem;">
                            Belum ada statistik SKPD yang tercatat.
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
</div>
@endsection

@push('scripts')
<script>
    function refreshDashboardCache() {
        const btn = document.getElementById('btn-refresh-stats');
        btn.disabled = true;
        btn.textContent = 'Memperbarui...';

        fetch('{{ route("admin.dashboard.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                document.getElementById('stat-total-asn').textContent = Number(data.data.total_asn).toLocaleString('id-ID');
                document.getElementById('stat-total-terbit').textContent = Number(data.data.total_terbit).toLocaleString('id-ID');
                document.getElementById('stat-total-antrian').textContent = Number(data.data.total_antrian).toLocaleString('id-ID');
                document.getElementById('stat-total-warning').textContent = Number(data.data.total_warning_h3).toLocaleString('id-ID');
                window.showToast('Statistik dashboard berhasil diperbarui.', 'success');
            }
        })
        .catch(err => {
            console.error('Gagal refresh statistik:', err);
            window.showToast('Gagal memperbarui statistik cache.', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '↻ Perbarui Cache Statistik';
        });
    }
</script>
@endpush
