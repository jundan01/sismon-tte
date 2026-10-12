<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service Verifikasi Google reCAPTCHA.
 * Ref: SECURITY.md §5.2, ARCHITECTURE.md §6.1
 */
class CaptchaService
{
    /**
     * Verifikasi Google reCAPTCHA dari request.
     */
    public function verify(Request $request): bool
    {
        $recaptchaToken = $request->input('g-recaptcha-response');
        $secretKey = config('services.recaptcha.secret');

        if (empty($recaptchaToken) || empty($secretKey)) {
            return false;
        }

        try {
            $response = Http::timeout(3)->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $recaptchaToken,
                'remoteip' => $request->ip(),
            ]);

            if ($response->successful() && $response->json('success') === true) {
                return true;
            }

            Log::warning('CaptchaService: Verifikasi Google reCAPTCHA gagal.');
        } catch (\Throwable $e) {
            Log::info('CaptchaService: Google reCAPTCHA timeout/unreachable', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
