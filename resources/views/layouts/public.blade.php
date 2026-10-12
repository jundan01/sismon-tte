<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pendaftaran Mandiri TTE & Email Dinas') — Pemerintah Kota Cirebon</title>
    
    <!-- Zero-CDN Typography & Carbon Flat Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/ibm-plex.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body style="background-color: var(--surface-1);">

    <!-- Header Instansi Resmi -->
    <header style="background-color: var(--surface-dark); color: #ffffff; border-bottom: 3px solid var(--primary); padding: 0.875rem 1.5rem;">
        <div style="max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 0.875rem;">
                <div style="width: 36px; height: 36px; background-color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem;">
                    CRB
                </div>
                <div>
                    <div style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; line-height: 1.2;">
                        Pemerintah Kota Cirebon
                    </div>
                    <div style="font-size: 0.75rem; color: #a8a8a8;">
                        Dinas Komunikasi dan Informatika · Bidang Persandian
                    </div>
                </div>
            </div>

            <!-- Network Connectivity Pill -->
            <div id="network-status" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #262626; border: 1px solid #393939; padding: 3px 8px; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase;">
                <span id="network-dot" style="width: 8px; height: 8px; background-color: var(--success); display: inline-block;"></span>
                <span id="network-text" style="color: #c6c6c6;">Online</span>
            </div>
        </div>
    </header>

    <!-- Flash Alert Container -->
    <div style="max-width: 1000px; margin: 1.5rem auto 0; padding: 0 1rem;">
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

        @if($errors->any())
            <div class="alert-flat alert-danger">
                <strong>Perhatian:</strong> Terdapat kesalahan pengisian formulir. Harap periksa kembali input data Anda.
            </div>
        @endif
    </div>

    <!-- Main Content Container -->
    <main style="max-width: 1000px; margin: 0 auto; padding: 1.5rem 1rem 3rem;">
        @yield('content')
    </main>

    <!-- Footer Resmi -->
    <footer style="background-color: var(--canvas); border-top: 1px solid var(--hairline); padding: 1.5rem 1rem; text-align: center; font-size: 0.75rem; color: var(--ink-muted);">
        <div style="max-width: 1000px; margin: 0 auto;">
            <p><strong>Sistem Monitoring Penerbitan TTE & Pelayanan Email Dinas Resmi</strong></p>
            <p style="margin-top: 0.25rem;">© {{ date('Y') }} Dinas Komunikasi dan Informatika Kota Cirebon. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <!-- Zero-CDN Core JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        // Network Status Detector
        window.addEventListener('online', () => {
            document.getElementById('network-dot').style.backgroundColor = 'var(--success)';
            document.getElementById('network-text').textContent = 'Online';
            if (window.showToast) window.showToast('Koneksi internet terhubung kembali.', 'success');
        });
        window.addEventListener('offline', () => {
            document.getElementById('network-dot').style.backgroundColor = 'var(--warning)';
            document.getElementById('network-text').textContent = 'Offline (Draft Aman)';
            if (window.showToast) window.showToast('Koneksi internet terputus. Formulir tetap tersimpan di browser Anda.', 'warning');
        });
    </script>
    @stack('scripts')
</body>
</html>
