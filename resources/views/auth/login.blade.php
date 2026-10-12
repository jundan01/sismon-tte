<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Superadmin — Sistem Monitoring TTE & Email Dinas</title>
    <style>
        :root {
            --bg-canvas: #f4f4f4;
            --bg-surface: #ffffff;
            --text-primary: #161616;
            --text-secondary: #525252;
            --border-subtle: #e0e0e0;
            --border-strong: #8d8d8d;
            --interactive: #0f62fe;
            --danger: #da1e28;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            border-radius: 0 !important;
        }

        body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-top: 4px solid var(--interactive);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .app-badge {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            height: 44px;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            background: #ffffff;
            border: 1px solid var(--border-strong);
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.15s ease;
        }

        .form-input:focus {
            border-color: var(--interactive);
            box-shadow: inset 0 0 0 1px var(--interactive);
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            background: var(--interactive);
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease;
        }

        .btn-submit:hover {
            background: #0353e9;
        }

        .alert-box {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.8125rem;
            line-height: 1.4;
            border-left: 3px solid var(--danger);
            background: #fff1f1;
            color: #750e13;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="app-badge">Pemerintah Kota Cirebon</div>
        <h1 class="login-title">Masuk Superadmin</h1>
        <p class="login-subtitle">Sistem Monitoring TTE & Pelayanan Email Dinas</p>

        <div
            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #e8f5e9; border: 1px solid #24a148; color: #0e6027; font-size: 0.75rem; font-weight: 600; margin-bottom: 1.5rem; letter-spacing: 0.02em;">
            <span style="display: inline-block; width: 8px; height: 8px; background: #24a148;"></span>
            JARINGAN TERVERIFIKASI
        </div>

        <div id="login-alert" class="alert-box" style="{{ $errors->any() ? '' : 'display: none;' }}">
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            @endif
        </div>

        <form id="login-form" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email" class="form-label">Email Akun Superadmin</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email', ) }}"
                    placeholder="xxxxxxxxxxx@gmail.com" required autofocus autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-input"
                    placeholder="Masukkan kata sandi..." required autocomplete="current-password">
            </div>

            <button type="submit" id="btn-submit" class="btn-submit">
                <span>Masuk ke Panel Kontrol</span>
            </button>
        </form>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = this;
            const btn = document.getElementById('btn-submit');
            const alertBox = document.getElementById('login-alert');
            const originalBtnHtml = btn.innerHTML;

            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
            btn.disabled = true;
            btn.innerHTML = '<span>Memverifikasi Kredensial...</span>';

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    btn.innerHTML = '<span>Autentikasi Berhasil! Mengalihkan...</span>';
                    btn.style.background = '#24a148';
                    setTimeout(() => {
                        window.location.href = data.redirect_url || '/admin/dashboard';
                    }, 400);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    alertBox.replaceChildren();
                    const errP = document.createElement('p');
                    errP.textContent = data.message || 'Login Gagal';
                    alertBox.appendChild(errP);
                    alertBox.style.display = 'block';
                }
            } catch (err) {
                // Jika fetch gagal (misal koneksi terputus), fallback submit standar
                form.submit();
            }
        });
    </script>
</body>

</html>