<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model MasterJabatanGolongan — Master Jabatan & Golongan ASN.
 * Ref: SCHEMA.md §7.2, ARCHITECTURE.md §8
 *
 * @property int    $id
 * @property string $kategori   'JABATAN' | 'GOLONGAN'
 * @property string $kode
 * @property string $nama_label
 */
class MasterJabatanGolongan extends Model
{
    protected $table = 'master_jabatan_golongan';

    public $timestamps = false; // Hanya created_at, tanpa updated_at

    protected $fillable = [
        'uuid',
        'kategori',
        'kode',
        'nama_label',
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

    /**
     * Scope: Filter berdasarkan kategori JABATAN.
     */
    public function scopeJabatan($query)
    {
        return $query->where('kategori', 'JABATAN');
    }

    /**
     * Scope: Filter berdasarkan kategori GOLONGAN.
     */
    public function scopeGolongan($query)
    {
        return $query->where('kategori', 'GOLONGAN');
    }

    /**
     * Relasi: Jabatan digunakan oleh banyak pendaftaran.
     */
    public function pendaftaranSebagaiJabatan(): HasMany
    {
        return $this->hasMany(\App\Domain\Pendaftaran\Models\PendaftaranTte::class, 'jabatan_id');
    }

    /**
     * Relasi: Golongan digunakan oleh banyak pendaftaran.
     */
    public function pendaftaranSebagaiGolongan(): HasMany
    {
        return $this->hasMany(\App\Domain\Pendaftaran\Models\PendaftaranTte::class, 'golongan_id');
    }
}
