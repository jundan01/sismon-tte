<?php

namespace App\Domain\Master\Models;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Master Sub-Unit Kerja / Sub-Kategori Penempatan ASN di bawah SKPD.
 * Mendukung analitik pemecahan capaian adopsi TTE secara terstruktur dan dinamis.
 *
 * @property int $id
 * @property string $uuid
 * @property int $skpd_id
 * @property string $nama_sub_unit
 * @property string $kategori_penempatan
 * @property int|null $target_asn
 * @property bool $is_active
 */
class SkpdSubUnit extends Model
{
    protected $table = 'skpd_sub_units';

    protected $fillable = [
        'uuid',
        'skpd_id',
        'nama_sub_unit',
        'kategori_penempatan',
        'target_asn',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'target_asn' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function skpd(): BelongsTo
    {
        return $this->belongsTo(Skpd::class, 'skpd_id');
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(PendaftaranTte::class, 'sub_unit_kerja', 'nama_sub_unit');
    }

    /**
     * Label representasi kategori penempatan manusiawi.
     */
    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori_penempatan) {
            'RSUD'                => '🏥 Rumah Sakit (RSUD)',
            'UPTD_PUSKESMAS'      => '🩺 Puskesmas / UPTD',
            'SATUAN_PENDIDIKAN'   => '🏫 Satuan Pendidikan / Sekolah',
            'KELURAHAN'           => '🏘️ Kelurahan',
            'PENUGASAN_EKSTERNAL' => '🌐 Penugasan Eksternal / BUMD / Lembaga Mitra',
            default               => '🏢 Dinas / Badan Induk',
        };
    }
}
