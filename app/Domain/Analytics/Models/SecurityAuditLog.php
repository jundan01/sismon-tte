<?php

namespace App\Domain\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SecurityAuditLog — Log Audit Append-Only Terpartisi Bulanan.
 * Ref: SCHEMA.md §7.8, SECURITY.md §8
 *
 * @property int         $id
 * @property string      $created_at      (Partition Key)
 * @property int|null    $user_id
 * @property string      $action_type
 * @property string      $entity_name
 * @property int|null    $entity_id
 * @property string      $ip_address
 * @property string|null $user_agent
 * @property array|null  $payload_before
 * @property array|null  $payload_after
 */
class SecurityAuditLog extends Model
{
    protected $table = 'security_audit_logs';

    public $timestamps = false; // created_at dihandle oleh DEFAULT CURRENT_TIMESTAMP

    protected $fillable = [
        'user_id',
        'action_type',
        'entity_name',
        'entity_id',
        'ip_address',
        'user_agent',
        'payload_before',
        'payload_after',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_before' => 'array',
            'payload_after'  => 'array',
            'created_at'     => 'datetime',
        ];
    }

    /**
     * Relasi: Log dipicu oleh user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
