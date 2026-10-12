<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User — IAM Superadmin dengan TOTP MFA.
 * Ref: SCHEMA.md §7.3, SECURITY.md §6.1–6.2
 *
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string      $password
 * @property string|null $mfa_secret
 * @property bool        $is_mfa_enabled
 * @property string      $role
 * @property string|null $last_login_at
 * @property string|null $last_login_ip
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'mfa_secret',
        'mfa_recovery_codes',
        'is_mfa_enabled',
        'last_login_at',
        'last_login_ip',
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

    protected $hidden = [
        'password',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password'           => 'hashed',
            'mfa_secret'         => 'encrypted',
            'mfa_recovery_codes' => 'encrypted',
            'is_mfa_enabled'     => 'boolean',
            'last_login_at'      => 'datetime',
        ];
    }

    /**
     * Relasi: Verifikasi Tahap 1 yang dilakukan user ini.
     */
    public function verifikasiTahap1(): HasMany
    {
        return $this->hasMany(\App\Domain\Pendaftaran\Models\PendaftaranTte::class, 'verified_by');
    }

    /**
     * Relasi: Verifikasi Tahap 2 (sertifikat) yang dilakukan user ini.
     */
    public function verifikasiTahap2(): HasMany
    {
        return $this->hasMany(\App\Domain\Sertifikat\Models\SertifikatTte::class, 'verified_by_tahap2');
    }

    /**
     * Relasi: Audit logs yang dipicu oleh user ini.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(\App\Domain\Analytics\Models\SecurityAuditLog::class, 'user_id');
    }

    /**
     * Periksa apakah user adalah superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }
}
