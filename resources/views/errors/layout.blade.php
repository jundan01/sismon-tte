<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — Sistem Monitoring TTE Kota Cirebon</title>
    <style>
        :root {
            --bg-canvas: #f4f4f4;
            --bg-surface: #ffffff;
            --text-primary: #161616;
            --text-secondary: #525252;
            --color-danger: #da1e28;
            --color-warning: #f1c21b;
            --border-subtle: #e0e0e0;
            --border-strong: #8d8d8d;
            --interactive: #0f62fe;
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
        .error-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-top: 4px solid var(--border-color, var(--interactive));
            width: 100%;
            max-width: 520px;
            padding: 2.5rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .error-code {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }
        .error-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        .error-desc {
            font-size: 0.9375rem;
            line-height: 1.5;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        .btn-action {
            display: inline-block;
            background: var(--interactive);
            color: #ffffff;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-action:hover {
            background: #0353e9;
        }
    </style>
</head>
<body>
    <div class="error-card" style="--border-color: @yield('border-color', '#0f62fe');">
        <div class="error-code">@yield('code')</div>
        <div class="error-title">@yield('heading')</div>
        <div class="error-desc">@yield('message')</div>
        <div>
            <a href="{{ url('/') }}" class="btn-action">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>
