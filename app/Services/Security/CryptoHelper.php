<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Utility Enkripsi Deterministik Dua Arah & Keamanan Data Sensitif Skala Nasional.
 *
 * Menerapkan enkripsi AES-256-CBC deterministik (reversible & konsisten):
 * - Input yang sama selalu menghasilkan ciphertext yang identik.
 * - Ciphertext dapat didekripsi kembali ke plaintext asli.
 * - Kunci rahasia disimpan di .env (DETERMINISTIC_ENCRYPTION_KEY).
 *
 * Ref: SECURITY.md §7.1, SCHEMA.md §10
 */
class CryptoHelper
{
    /**
     * Resolve kunci enkripsi deterministik dari konfigurasi.
     * Menggunakan DETERMINISTIC_ENCRYPTION_KEY dari .env, fallback ke APP_KEY.
     */
    protected static function resolveDeterministicKey(): string
    {
        $key = config('crypto.deterministic_key');

        if (empty($key)) {
            $key = config('app.key');
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
    }

    /**
     * Derive IV deterministik dari kunci rahasia (16 byte untuk AES-256-CBC).
     * IV selalu identik untuk kunci yang sama sehingga menjamin konsistensi ciphertext.
     */
    protected static function deriveDeterministicIv(string $key): string
    {
        return substr(hash('sha256', 'deterministic-iv:' . $key, true), 0, 16);
    }

    /**
     * Enkripsi field teks secara deterministik (AES-256-CBC, reversible & konsisten).
     * Input yang sama selalu menghasilkan ciphertext yang identik.
     */
    public static function encryptField(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = self::resolveDeterministicKey();
        $iv = self::deriveDeterministicIv($key);
        $cipher = config('crypto.cipher', 'aes-256-cbc');

        $encrypted = openssl_encrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            Log::error('CryptoHelper: Gagal mengenkripsi data sensitif secara deterministik.');
            return null;
        }

        return base64_encode($encrypted);
    }

    /**
     * Dekripsi field terenkripsi deterministik (AES-256-CBC) kembali ke plaintext asli.
     */
    public static function decryptField(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return null;
        }

        try {
            $key = self::resolveDeterministicKey();
            $iv = self::deriveDeterministicIv($key);
            $cipher = config('crypto.cipher', 'aes-256-cbc');

            $decoded = base64_decode($ciphertext, true);
            if ($decoded === false) {
                Log::warning('CryptoHelper: Ciphertext bukan Base64 valid.');
                return null;
            }

            $decrypted = openssl_decrypt($decoded, $cipher, $key, OPENSSL_RAW_DATA, $iv);

            if ($decrypted === false) {
                Log::warning('CryptoHelper: Gagal mendekripsi ciphertext data sensitif.');
                return null;
            }

            return $decrypted;
        } catch (\Throwable $e) {
            Log::warning('CryptoHelper: Gagal mendekripsi ciphertext data sensitif', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * @deprecated Tidak digunakan lagi. Pencarian & constraint unik kini menggunakan
     * ciphertext deterministik langsung pada kolom nik/nip via encryptField().
     *
     * Hasilkan Blind Index HMAC-SHA256 deterministik (dipertahankan untuk kompatibilitas migrasi data).
     */
    public static function generateBlindIndex(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $appKey = config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }

        return hash_hmac('sha256', trim((string)$value), $appKey);
    }

    /**
     * Hitung SHA-256 Checksum dari berkas fisik.
     */
    public static function calculateFileChecksum(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        return hash_file('sha256', $filePath);
    }

    /**
     * Verifikasi integritas berkas terhadap checksum yang diharapkan (Timing-Attack Safe).
     */
    public static function verifyFileChecksum(string $filePath, string $expectedChecksum): bool
    {
        $actual = self::calculateFileChecksum($filePath);
        if ($actual === null) {
            return false;
        }

        return hash_equals(strtolower($expectedChecksum), strtolower($actual));
    }

    /**
     * Buat signed token berbatas waktu (TTL).
     */
    public static function createSignedPayload(array $payload, int $ttlSeconds = 300): string
    {
        $data = [
            'p' => $payload,
            'exp' => now()->timestamp + $ttlSeconds,
            'iat' => now()->timestamp,
        ];

        return Crypt::encryptString(json_encode($data));
    }

    /**
     * Verifikasi dan ekstrak payload dari signed token.
     */
    public static function extractSignedPayload(string $token): ?array
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $data = json_decode($decrypted, true);

            if (!is_array($data) || !isset($data['exp'], $data['p'])) {
                return null;
            }

            if (now()->timestamp > $data['exp']) {
                return null; // Token kedaluwarsa
            }

            return $data['p'];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
