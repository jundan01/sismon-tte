<?php

namespace App\Http\Requests;

use App\Services\EmailGeneratorService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest Validasi Penetapan & Rekap Email Dinas Definitif oleh Superadmin.
 * Mengunci domain @cirebonkota.go.id secara permanen agar superadmin hanya menginput nama/prefix.
 * Ref: PRD.md §7.2, ARCHITECTURE.md §6.2
 */
class RekapEmailDinasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperadmin();
    }

    /**
     * Kunci domain @cirebonkota.go.id secara permanen:
     * Mengisolasi input nama/prefix pengguna dan menggabungkannya secara otomatis
     * dengan domain resmi @cirebonkota.go.id untuk meminimalisir kesalahan pengetikan domain.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email_final_terverifikasi')) {
            $raw = trim((string)$this->input('email_final_terverifikasi'));
            if ($raw !== '') {
                $prefix = str_contains($raw, '@') ? explode('@', $raw)[0] : $raw;
                $prefix = trim($prefix);
                $this->merge([
                    'email_final_terverifikasi' => strtolower($prefix . '@' . EmailGeneratorService::DEFAULT_DOMAIN),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'email_final_terverifikasi' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    $domain = strtolower(substr(strrchr($value, '@'), 1));
                    if ($domain !== EmailGeneratorService::DEFAULT_DOMAIN) {
                        $fail('Alamat email dinas definitif harus menggunakan domain resmi @' . EmailGeneratorService::DEFAULT_DOMAIN . '.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email_final_terverifikasi.required' => 'Nama / username email definitif yang akan direkap wajib diisi.',
            'email_final_terverifikasi.email'    => 'Format alamat email tidak valid.',
        ];
    }
}
