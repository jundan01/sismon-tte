<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Multi-layer Rate Limiting Middleware.
 * Melindungi endpoint sensitif (pendaftaran, login, export) dari serangan brute-force & DoS.
 * Ref: SECURITY.md §4.2, ARCHITECTURE.md §1.1
 */
class ThrottlingMiddleware
{
    public function handle(Request $request, Closure $next, string $limiterName = 'general', int $maxAttempts = 30, int $decaySeconds = 60): Response
    {
        $key = $this->resolveRequestSignature($request, $limiterName);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak permintaan. Silakan coba kembali dalam ' . $seconds . ' detik.',
                    'retry_after' => $seconds,
                ], 429)->header('Retry-After', (string)$seconds);
            }

            return response(
                view('errors.429', ['retryAfter' => $seconds])->render(),
                429
            )->header('Retry-After', (string)$seconds);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string)$maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string)RateLimiter::remaining($key, $maxAttempts));

        return $response;
    }

    /**
     * Tentukan signature unik request berdasarkan IP dan konteks.
     */
    protected function resolveRequestSignature(Request $request, string $limiterName): string
    {
        return sha1($limiterName . '|' . $request->ip());
    }
}
