<?php

namespace App\Domain\Pendaftaran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model PendaftaranTte — Transaksi Utama Pendaftaran TTE.
 * Enkripsi deterministik dua arah AES-256-CBC pada NIK/NIP.
 * Ref: SCHEMA.md §7.4 & §10, ARCHITECTURE.md §8
 *
 * @property int         $id
 * @property string      $nomor_referensi
 * @property string      $nama_lengkap
 * @property string      $nik             (encrypted deterministik)
 * @property string      $nip             (encrypted deterministik)
 * @property string      $no_hp
 * @property int         $skpd_id
 * @property int|null    $jabatan_id
 * @property int|null    $golongan_id
 * @property bool        $status_memiliki_email
 * @property string|null $email_existing
 * @property string|null $email_usulan
 * @property string|null $email_final_terverifikasi
 * @property bool        $is_email_recap_confirmed
 * @property string      $status
 * @property string|null $catatan_penolakan
 * @property int|null    $verified_by
 * @property string|null $verified_at
 * @property string|null $verified_ip
 */
class PendaftaranTte extends Model
{
    protected $table = 'pendaftaran_tte';

    protected $fillable = [
        'uuid',
        'nomor_referensi',
        'nama_lengkap',
        'nik',
        'nip',
        'no_hp',
        'skpd_id',
        'deskripsi',
        'jabatan_id',
        'golongan_id',
        'status_memiliki_email',
        'email_existing',
        'email_usulan',
        'email_final_terverifikasi',
        'is_email_recap_confirmed',
        'status',
        'catatan_penolakan',
        'verified_by',
        'verified_at',
        'verified_ip',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status_memiliki_email'    => 'boolean',
            'is_email_recap_confirmed' => 'boolean',
            'verified_at'              => 'datetime',
        ];
    }

    /**
     * Mutator NIK: Enkripsi deterministik AES-256-CBC dua arah.
     * Ref: SCHEMA.md §10
     */
    public function setNikAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['nik'] = \App\Services\Security\CryptoHelper::encryptField($value);
        }
    }

    /**
     * Mutator NIP: Enkripsi deterministik AES-256-CBC dua arah.
     * Ref: SCHEMA.md §10
     */
    public function setNipAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['nip'] = \App\Services\Security\CryptoHelper::encryptField($value);
        }
    }

    /**
     * Accessor NIK: Dekripsi otomatis.
     */
    public function getNikAttribute(?string $value): ?string
    {
        return \App\Services\Security\CryptoHelper::decryptField($value);
    }

    /**
     * Accessor NIP: Dekripsi otomatis.
     */
    public function getNipAttribute(?string $value): ?string
    {
        return \App\Services\Security\CryptoHelper::decryptField($value);
    }

    // ── Relasi ──────────────────────────────────────────────────

    public function skpd(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Master\Models\Skpd::class, 'skpd_id');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Master\Models\MasterJabatanGolongan::class, 'jabatan_id');
    }

    public function golongan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Master\Models\MasterJabatanGolongan::class, 'golongan_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function sertifikat(): HasOne
    {
        return $this->hasOne(\App\Domain\Sertifikat\Models\SertifikatTte::class, 'pendaftaran_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeMenungguVerifikasi($query)
    {
        return $query->where('status', 'MENUNGGU_VERIFIKASI');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', 'DISETUJUI');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', 'DITOLAK');
    }
}
