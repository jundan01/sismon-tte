<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Skpd — Master Unit Kerja / Instansi Kedinasan.
 * Ref: SCHEMA.md §7.1, ARCHITECTURE.md §8
 *
 * @property int    $id
 * @property string $kode_skpd
 * @property string $nama_skpd
 * @property int    $total_asn
 * @property bool   $is_active
 */
class Skpd extends Model
{
    protected $table = 'skpd';

    protected $fillable = [
        'uuid',
        'kode_skpd',
        'nama_skpd',
        'total_asn',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_asn' => 'integer',
        ];
    }

    /**
     * Relasi: SKPD menaungi banyak pendaftaran TTE.
     */
    public function pendaftaranTte(): HasMany
    {
        return $this->hasMany(\App\Domain\Pendaftaran\Models\PendaftaranTte::class, 'skpd_id');
    }

    /**
     * Relasi: SKPD menaungi banyak sub-unit / sub-kategori penempatan ASN.
     */
    public function subUnits(): HasMany
    {
        return $this->hasMany(SkpdSubUnit::class, 'skpd_id');
    }

    /**
     * Scope: Hanya SKPD aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
