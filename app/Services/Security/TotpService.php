<?php

namespace App\Services\Security;

use Illuminate\Support\Str;

/**
 * Service Multi-Factor Authentication TOTP (RFC 6238 / Google Authenticator).
 * Implementasi murni PHP tanpa dependensi library eksternal yang rentan.
 * Ref: SECURITY.md §6.2, PRD.md FR-21
 */
class TotpService
{
    public const TIME_STEP = 30; // 30 Detik per kode OTP
    public const DIGITS = 6;
    public const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Hasilkan secret key Base32 acak (16 karakter).
     */
    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        $maxIndex = strlen(self::BASE32_CHARS) - 1;

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $maxIndex)];
        }

        return $secret;
    }

    /**
     * Hasilkan URI otpauth:// untuk pemindaian QR Code pada Google Authenticator.
     */
    public function getOtpAuthUrl(string $email, string $secret, string $issuer = 'Diskominfo Kota Cirebon'): string
    {
        $label = rawurlencode("Sistem Monitoring TTE ({$email})");
        $issuerEncoded = rawurlencode($issuer);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Verifikasi kode 6-digit TOTP yang dimasukkan pengguna (toleransi ±1 time step).
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int)floor(time() / self::TIME_STEP);

        // Periksa time slice saat ini, sebelumnya (-1), dan sesudahnya (+1) untuk toleransi latensi jam
        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->calculateCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hitung kode 6-digit TOTP untuk time slice tertentu.
     */
    public function calculateCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);

        // Format binary counter (8 bytes, Big-Endian)
        $time = pack('N*', 0) . pack('N*', $timeSlice);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic Truncation (RFC 4226)
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string)$otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Hasilkan 8 recovery backup codes darurat.
     *
     * @return array<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(5) . '-' . Str::random(5));
        }

        return $codes;
    }

    /**
     * Decode string Base32 menjadi biner.
     */
    protected function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            $val = strpos(self::BASE32_CHARS, $char);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
