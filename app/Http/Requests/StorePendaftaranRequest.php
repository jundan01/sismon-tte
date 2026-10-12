<?php

namespace App\Http\Requests;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Services\Security\CryptoHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * FormRequest Validasi Pendaftaran Publik ASN Mandiri.
 * Ref: PRD.md §7.1, SECURITY.md §5.1 & §7.1
 */
class StorePendaftaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Form publik tanpa login
    }

    public function rules(): array
    {
        return [
            'nama_lengkap'          => ['required', 'string', 'max:255'],
            'nik'                   => ['required', 'string', 'digits:16'],
            'nip'                   => ['required', 'string', 'digits:18'],
            'no_hp'                 => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9\+\-\s]+$/'],
            'skpd_id'               => ['required', 'integer', 'exists:skpd,id'],
            'deskripsi'             => ['nullable', 'string', 'max:1000'],
            'jabatan_id'            => ['nullable', 'integer', 'exists:master_jabatan_golongan,id'],
            'golongan_id'           => ['nullable', 'integer', 'exists:master_jabatan_golongan,id'],
            'status_memiliki_email' => ['required', 'boolean'],
            'email_existing'        => ['nullable', 'required_if:status_memiliki_email,true', 'email', 'max:255'],
            
            // Google reCAPTCHA verification
            'g-recaptcha-response'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required'          => 'Nama lengkap ASN wajib diisi.',
            'nik.required'                   => 'Nomor Induk Kependudukan (NIK) wajib diisi.',
            'nik.digits'                     => 'NIK harus berjumlah tepat 16 digit angka.',
            'nip.required'                   => 'Nomor Induk Pegawai (NIP) wajib diisi.',
            'nip.digits'                     => 'NIP harus berjumlah tepat 18 digit angka.',
            'no_hp.required'                 => 'Nomor kontak telepon/WhatsApp aktif wajib diisi.',
            'skpd_id.required'               => 'Unit kerja / SKPD wajib dipilih.',
            'skpd_id.exists'                 => 'Unit kerja yang dipilih tidak valid.',
            'deskripsi.max'                  => 'Deskripsi penempatan kerja maksimal 1000 karakter.',
            'status_memiliki_email.required' => 'Status kepemilikan email dinas wajib dipilih.',
            'email_existing.required_if'     => 'Alamat email yang sudah dimiliki wajib dicantumkan jika memilih sudah punya email.',
            'email_existing.email'           => 'Format alamat email tidak valid.',
        ];
    }

    /**
     * Custom Validator untuk reCAPTCHA dan Anti-Enumeration Uniqueness Check.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // 1. Verifikasi Google reCAPTCHA
            if (!app()->environment('testing') && !app()->runningUnitTests()) {
                $recaptchaToken = $this->input('g-recaptcha-response');
                $secretKey = config('services.recaptcha.secret');

                if (empty($recaptchaToken) || empty($secretKey)) {
                    $v->errors()->add('captcha', 'Verifikasi keamanan reCAPTCHA wajib diselesaikan.');
                    return;
                }

                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(3)->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                        'secret' => $secretKey,
                        'response' => $recaptchaToken,
                        'remoteip' => $this->ip(),
                    ]);

                    if (!$response->successful() || $response->json('success') !== true) {
                        $v->errors()->add('captcha', 'Verifikasi keamanan reCAPTCHA tidak valid atau telah kedaluwarsa.');
                        return;
                    }
                } catch (\Throwable $e) {
                    $v->errors()->add('captcha', 'Verifikasi keamanan reCAPTCHA gagal terhubung. Silakan coba lagi.');
                    return;
                }
            }

            // 2. Anti-Enumeration & Partial Unique Check untuk NIK & NIP aktif
            $nik = $this->input('nik');
            $nip = $this->input('nip');

            if ($nik && strlen($nik) === 16) {
                $nikEncrypted = CryptoHelper::encryptField($nik);
                $isNikActive = PendaftaranTte::where('nik', $nikEncrypted)
                    ->where('status', '!=', 'DITOLAK')
                    ->exists();

                if ($isNikActive) {
                    $v->errors()->add('nik', 'NIK/NIP sudah terdata dalam antrian atau telah terverifikasi.');
                }
            }

            if ($nip && strlen($nip) === 18) {
                $nipEncrypted = CryptoHelper::encryptField($nip);
                $isNipActive = PendaftaranTte::where('nip', $nipEncrypted)
                    ->where('status', '!=', 'DITOLAK')
                    ->exists();

                if ($isNipActive) {
                    $v->errors()->add('nip', 'NIK/NIP sudah terdata dalam antrian atau telah terverifikasi.');
                }
            }
        });
    }
}

