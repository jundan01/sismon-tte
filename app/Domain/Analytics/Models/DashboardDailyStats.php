<?php

namespace App\Domain\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model DashboardDailyStats — Fast Aggregator Statistik Harian.
 * Ref: SCHEMA.md §7.7, ARCHITECTURE.md §6.4
 *
 * @property int    $id
 * @property string $stat_date
 * @property int    $total_asn
 * @property int    $total_terbit
 * @property int    $total_antrian
 * @property int    $total_warning_h3
 */
class DashboardDailyStats extends Model
{
    protected $table = 'dashboard_daily_stats';

    public $timestamps = false; // Hanya updated_at manual

    protected $fillable = [
        'stat_date',
        'total_asn',
        'total_terbit',
        'total_antrian',
        'total_warning_h3',
        'total_warning_h7',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'stat_date'        => 'date',
            'total_asn'        => 'integer',
            'total_terbit'     => 'integer',
            'total_antrian'    => 'integer',
            'total_warning_h3' => 'integer',
            'total_warning_h7' => 'integer',
            'updated_at'       => 'datetime',
        ];
    }
}
