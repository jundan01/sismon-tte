<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware Sanitasi Input & Pencegahan Payload Berbahaya / XSS.
 * Ref: SECURITY.md §5.1
 */
class SanitizeInput
{
    /**
     * Daftar field yang dikecualikan dari sanitasi agresif (misalnya password).
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (!empty($input)) {
            $cleaned = $this->cleanArray($input);
            $request->replace($cleaned);
        }

        return $next($request);
    }

    /**
     * Rekursif membersihkan array input.
     */
    protected function cleanArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                $result[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->cleanArray($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->cleanString($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Sanitasi string: bersihkan null-bytes, control characters, dan spasi berlebih.
     */
    protected function cleanString(string $value): ?string
    {
        // Hilangkan null bytes
        $value = str_replace(chr(0), '', $value);

        // Hilangkan karakter kontrol non-printable (kecuali tab \t dan newline \r \n)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // Trim spasi
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
