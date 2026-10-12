<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Engine Matematika Local CAPTCHA Fallback Berpenanda Tangan HMAC.
 * Menjamin ASN di pelosok tetap dapat mendaftar saat akses internet eksternal terganggu.
 * Ref: SECURITY.md §5.2, ARCHITECTURE.md §6.1
 */
class LocalCaptchaService
{
    public const VALIDITY_SECONDS = 300; // 5 Menit

    /**
     * Hasilkan tantangan matematika baru beserta signature terenkripsi.
     *
     * @return array{question: string, signature: string}
     */
    public function generate(): array
    {
        $num1 = random_int(1, 20);
        $num2 = random_int(1, 15);
        $operator = random_int(0, 1) === 0 ? '+' : '-';

        // Pastikan hasil pengurangan tidak negatif untuk kenyamanan user
        if ($operator === '-' && $num1 < $num2) {
            [$num1, $num2] = [$num2, $num1];
        }

        $answer = $operator === '+' ? ($num1 + $num2) : ($num1 - $num2);
        $timestamp = now()->timestamp;

        $payload = "{$answer}|{$timestamp}";
        $signature = Crypt::encryptString($payload);

        return [
            'question' => "Berapa hasil dari {$num1} {$operator} {$num2}?",
            'signature' => $signature,
        ];
    }

    /**
     * Verifikasi jawaban matematika user terhadap signature.
     */
    public function verify(?string $answer, ?string $signature): bool
    {
        if ($answer === null || $signature === null || trim($answer) === '' || trim($signature) === '') {
            return false;
        }

        try {
            $decrypted = Crypt::decryptString($signature);
            $parts = explode('|', $decrypted);

            if (count($parts) !== 2) {
                return false;
            }

            [$expectedAnswer, $timestamp] = $parts;

            // Periksa batas waktu (TTL 5 menit)
            if ((now()->timestamp - (int)$timestamp) > self::VALIDITY_SECONDS) {
                return false;
            }

            // Periksa jawaban dengan timing-safe comparison
            return hash_equals(trim((string)$expectedAnswer), trim((string)$answer));
        } catch (DecryptException $e) {
            return false;
        }
    }
}
