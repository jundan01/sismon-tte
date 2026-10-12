<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Kontrol Superadmin') — Monitoring TTE Kota Cirebon</title>
    
    <!-- Zero-CDN Typography & Carbon Flat Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/ibm-plex.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

    <div class="admin-wrapper">
        <!-- ── SIDEBAR SUPERADMIN (240px Carbon Dark) ───────────── -->
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <span>TTE MONITORING</span>
                    <span class="sidebar-brand-badge">PROD</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Utama & Analitik</div>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                    <span>Dashboard Transaksi</span>
                </a>
                <a href="{{ route('admin.capaian-skpd.index') }}" class="{{ request()->routeIs('admin.capaian-skpd*') ? 'active' : '' }}">
                    <span>Capaian Adopsi SKPD</span>
                    <span class="badge" style="background:#393939; color:#fff;">%</span>
                </a>

                <div class="nav-section-title">Operasional Verifikasi</div>
                <a href="{{ route('admin.verifikasi.index') }}" class="{{ request()->routeIs('admin.verifikasi*') ? 'active' : '' }}">
                    <span>Verifikasi Tahap 1</span>
                    <span class="badge badge-menunggu">FIFO</span>
                </a>
                <a href="{{ route('admin.penerbitan.index') }}" class="{{ request()->routeIs('admin.penerbitan*') ? 'active' : '' }}">
                    <span>Penerbitan Tahap 2</span>
                    <span class="badge badge-aktif">TTE</span>
                </a>

                <div class="nav-section-title">Pelaporan & Ekspor</div>
                <a href="javascript:void(0)" onclick="openModal('modal-streaming-export')">
                    <span>Ekspor Massal CSV</span>
                    <span class="badge" style="background:#393939; color:#fff;">STREAM</span>
                </a>
                <a href="{{ route('admin.capaian-skpd.export') }}">
                    <span>Ekspor Capaian SKPD</span>
                    <span class="badge" style="background:#393939; color:#fff;">CSV</span>
                </a>

                <div class="nav-section-title">Manajemen Master Data</div>
                <a href="{{ route('admin.master.skpd.index') }}" class="{{ request()->routeIs('admin.master.skpd*') ? 'active' : '' }}">
                    <span>Unit Kerja (SKPD)</span>
                </a>
                <a href="{{ route('admin.master.jabatan-golongan.index') }}" class="{{ request()->routeIs('admin.master.jabatan-golongan*') ? 'active' : '' }}">
                    <span>Jabatan & Golongan</span>
                </a>
                <a href="{{ route('admin.master.users.index') }}" class="{{ request()->routeIs('admin.master.users*') ? 'active' : '' }}">
                    <span>Akun Superadmin & MFA</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div style="font-weight: 600; color: #fff; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Superadmin' }}</div>
                <div style="font-size: 0.6875rem; color: #8d8d8d;">{{ auth()->user()->email ?? 'admin@cirebonkota.go.id' }}</div>
            </div>
        </aside>

        <!-- ── MAIN CONTENT AREA ────────────────────────────────── -->
        <div class="admin-main">
            <!-- Topbar Navigation -->
            <header class="admin-topbar">
                <div class="topbar-breadcrumb">
                    <strong>SISTEM MONITORING TTE</strong>
                    <span>/</span>
                    <span>@yield('breadcrumb', 'Dashboard')</span>
                </div>

                <div class="topbar-actions">
                    <!-- Status VPN Gateway -->
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 8px; background: #e8f5e9; border: 1px solid #24a148; color: #0e6027; font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.02em;">
                        <span style="display: inline-block; width: 6px; height: 6px; background: #24a148;"></span>
                        VPN DKIS TERVERIFIKASI
                    </div>

                    <div style="font-size: 0.75rem; color: var(--ink-secondary); font-family: 'IBM Plex Mono', monospace;">
                        {{ date('d M Y') }} · <span id="clock-wib">--:--:--</span> WIB
                    </div>

                    <!-- Tombol Logout -->
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" style="border-color: var(--hairline);">
                            Keluar
                        </button>
                    </form>
                </div>
            </header>

            <!-- Flash Alert Container -->
            <div style="padding: 1.5rem 1.5rem 0;">
                @if(session('success'))
                    <div class="alert-flat alert-success">
                        <strong>Berhasil:</strong> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert-flat alert-danger">
                        <strong>Kesalahan:</strong> {{ session('error') }}
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- ── MODAL GLOBAL: STREAMING MASS EXPORT (RAM < 20MB) ─── -->
    <div class="modal-backdrop" id="modal-streaming-export">
        <div class="modal-flat">
            <div class="modal-header">
                <h3>Ekspor Laporan Transaksi (Cursor Streaming)</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal-streaming-export')">✕</button>
            </div>
            <form action="{{ route('admin.export.csv') }}" method="GET" id="form-export-stream">
                <div class="modal-body">
                    <p style="font-size: 0.8125rem; color: var(--ink-secondary); margin-bottom: 1.25rem;">
                        Sistem menggunakan <strong>Database Cursor Streaming</strong> dengan pemakaian memori PHP stabil di bawah 20MB sekalipun mengekspor 100.000+ data transaksi.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Status Pendaftaran</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status Pendaftaran</option>
                            <option value="MENUNGGU_VERIFIKASI">MENUNGGU_VERIFIKASI</option>
                            <option value="DISETUJUI">DISETUJUI</option>
                            <option value="DITOLAK">DITOLAK</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rentang Tanggal Pendaftaran</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <input type="date" name="start_date" class="form-control" placeholder="Dari Tanggal">
                            <input type="date" name="end_date" class="form-control" placeholder="Sampai Tanggal">
                        </div>
                    </div>

                    <div id="export-shimmer" style="display: none; margin-top: 1rem;">
                        <div style="font-size: 0.75rem; color: var(--primary); margin-bottom: 4px; font-weight: 600;">
                            Menyiapkan stream data CSV...
                        </div>
                        <div class="shimmer-bar"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-streaming-export')">Batal</button>
                    <button type="submit" class="btn btn-primary" onclick="startExportShimmer()">Unduh CSV Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Zero-CDN Core JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        // Live WIB Clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false, timeZone: 'Asia/Jakarta' });
            const clockEl = document.getElementById('clock-wib');
            if (clockEl) clockEl.textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function startExportShimmer() {
            document.getElementById('export-shimmer').style.display = 'block';
            setTimeout(() => {
                closeModal('modal-streaming-export');
                document.getElementById('export-shimmer').style.display = 'none';
            }, 3000);
        }
    </script>
    @stack('scripts')
</body>
</html>
