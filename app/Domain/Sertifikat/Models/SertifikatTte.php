<?php

namespace App\Domain\Sertifikat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SertifikatTte — Penerbitan & Monitoring Siklus Hidup Sertifikat.
 * Ref: SCHEMA.md §7.6, ARCHITECTURE.md §8
 *
 * @property int         $id
 * @property int         $pendaftaran_id
 * @property string      $email_dinas
 * @property string      $tanggal_penerbitan
 * @property string      $tanggal_kadaluarsa
 * @property string      $status_sertifikat
 * @property int         $verified_by_tahap2
 * @property string      $verified_at_tahap2
 * @property string|null $verified_ip_tahap2
 */
class SertifikatTte extends Model
{
    protected $table = 'sertifikat_tte';

    protected $fillable = [
        'uuid',
        'pendaftaran_id',
        'email_dinas',
        'tanggal_penerbitan',
        'tanggal_kadaluarsa',
        'status_sertifikat',
        'verified_by_tahap2',
        'verified_at_tahap2',
        'verified_ip_tahap2',
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
            'tanggal_penerbitan' => 'date:Y-m-d',
            'tanggal_kadaluarsa' => 'date:Y-m-d',
            'verified_at_tahap2' => 'datetime',
        ];
    }

    // ── Relasi ──────────────────────────────────────────────────

    /**
     * Relasi 1:1: Sertifikat milik satu pendaftaran.
     */
    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pendaftaran\Models\PendaftaranTte::class, 'pendaftaran_id');
    }

    /**
     * Relasi: Verifikator Tahap 2.
     */
    public function verifikatorTahap2(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by_tahap2');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status_sertifikat', 'AKTIF');
    }

    public function scopeWarningH7($query)
    {
        return $query->whereIn('status_sertifikat', ['WARNING_H7', 'WARNING_H3']);
    }

    public function scopeWarningH3($query)
    {
        return $query->whereIn('status_sertifikat', ['WARNING_H7', 'WARNING_H3']);
    }

    public function scopeExpired($query)
    {
        return $query->where('status_sertifikat', 'EXPIRED');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status_sertifikat', 'REVOKED');
    }

    /**
     * Hitung sisa hari menuju tanggal kedaluwarsa (integer bulat berbasis start-of-day).
     * Nilai > 0: H-X (misal 7 = 7 hari lagi)
     * Nilai 0: Hari ini (H-0)
     * Nilai < 0: Sudah lewat (misal -17 = lewat 17 hari yang lalu)
     */
    public function getSisaHariAttribute(): ?int
    {
        if (! $this->tanggal_kadaluarsa) {
            return null;
        }

        return (int) today()->diffInDays($this->tanggal_kadaluarsa->copy()->startOfDay(), false);
    }

    /**
     * Data badge status masa berlaku yang rapi, manusiawi, dan mudah dipahami.
     *
     * @return array{label: string, class: string, is_urgent: bool}
     */
    public function getStatusMasaBerlakuBadgeAttribute(): array
    {
        $days = $this->sisa_hari;

        if ($days === null) {
            return [
                'label'     => '-',
                'class'     => 'badge',
                'is_urgent' => false,
            ];
        }

        if ($days > 7) {
            return [
                'label'     => "H-{$days} ({$days} hari lagi)",
                'class'     => 'badge badge-aktif',
                'is_urgent' => false,
            ];
        }

        if ($days > 1) {
            return [
                'label'     => "H-{$days} ({$days} hari lagi)",
                'class'     => 'badge badge-warning-h7',
                'is_urgent' => true,
            ];
        }

        if ($days === 1) {
            return [
                'label'     => 'H-1 (Besok)',
                'class'     => 'badge badge-warning-h7',
                'is_urgent' => true,
            ];
        }

        if ($days === 0) {
            return [
                'label'     => 'H-0 (Hari Ini)',
                'class'     => 'badge badge-warning-h7',
                'is_urgent' => true,
            ];
        }

        // $days < 0 (sudah lewat)
        $lewat = abs($days);
        return [
            'label'     => "Kedaluwarsa (Lewat {$lewat} hari)",
            'class'     => 'badge badge-expired',
            'is_urgent' => true,
        ];
    }

    /**
     * Cek apakah sertifikat memasuki zona H-7 kedaluwarsa.
     */
    public function isApproachingExpiry(): bool
    {
        if (! $this->tanggal_kadaluarsa) {
            return false;
        }

        $sisa = $this->sisa_hari;
        return $sisa !== null && $sisa >= 0 && $sisa <= 7;
    }
}
