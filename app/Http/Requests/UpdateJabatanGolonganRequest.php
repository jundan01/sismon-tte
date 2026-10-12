<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest Validasi Pembaruan Jabatan & Golongan ASN oleh Superadmin.
 * Ref: PRD.md §7.2, SECURITY.md §5.1 & §6.1
 */
class UpdateJabatanGolonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'jabatan_id' => [
                'nullable',
                'integer',
                Rule::exists('master_jabatan_golongan', 'id')->where('kategori', 'JABATAN'),
            ],
            'golongan_id' => [
                'nullable',
                'integer',
                Rule::exists('master_jabatan_golongan', 'id')->where('kategori', 'GOLONGAN'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'jabatan_id.integer'  => 'Format ID Jabatan tidak valid.',
            'jabatan_id.exists'   => 'Jabatan yang dipilih tidak terdaftar pada master data Jabatan.',
            'golongan_id.integer' => 'Format ID Golongan tidak valid.',
            'golongan_id.exists'  => 'Golongan yang dipilih tidak terdaftar pada master data Golongan.',
        ];
    }
}
