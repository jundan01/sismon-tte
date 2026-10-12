<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai reverse proxy / Cloudflare Tunnel
        $middleware->trustProxies(at: '*');

        // Daftarkan middleware keamanan global pada web group
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        // Daftarkan alias middleware
        $middleware->alias([
            'vpn.gateway'      => \App\Http\Middleware\EnsureVpnAccess::class,
            'throttle.custom'  => \App\Http\Middleware\ThrottlingMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'sanitize.input'   => \App\Http\Middleware\SanitizeInput::class,
        ]);

        // Prioritas Eksekusi: VPN Gateway Perimeter mendahului otentikasi
        $middleware->priority([
            \App\Http\Middleware\EnsureVpnAccess::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Penanganan Error Terpusat DRY untuk JSON/AJAX API requests
        $jsonResponder = function (Request $request, string $message, int $statusCode, array $extra = []) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(array_merge([
                    'success' => false,
                    'message' => $message,
                ], $extra), $statusCode);
            }
            return null;
        };

        $exceptions->render(fn (ValidationException $e, Request $req) =>
            $jsonResponder($req, 'Validasi data tidak berhasil.', 422, ['errors' => $e->errors()]));

        $exceptions->render(fn (AuthenticationException $e, Request $req) =>
            $jsonResponder($req, 'Sesi tidak valid atau telah berakhir. Silakan login kembali.', 401));

        $exceptions->render(fn (AuthorizationException $e, Request $req) =>
            $jsonResponder($req, 'Akses ditolak. Anda tidak memiliki wewenang untuk tindakan ini.', 403));

        $exceptions->render(fn (AccessDeniedHttpException $e, Request $req) =>
            $jsonResponder($req, 'Akses ditolak (403 Forbidden).', 403));

        $exceptions->render(fn (NotFoundHttpException $e, Request $req) =>
            $jsonResponder($req, 'Sumber daya atau endpoint yang diminta tidak ditemukan (404 Not Found).', 404));

        $exceptions->render(fn (HttpException $e, Request $req) =>
            $jsonResponder($req, $e->getMessage() ?: 'Terjadi kesalahan HTTP (' . $e->getStatusCode() . ')', $e->getStatusCode()));
    })->create();
