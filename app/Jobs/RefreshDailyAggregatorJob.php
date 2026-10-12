<?php

namespace App\Jobs;

use App\Domain\Analytics\Actions\RefreshDailyAggregatorAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Job Asinkron Pembaruan Agregator Harian & Redis Cache Dashboard.
 * Ref: ARCHITECTURE.md §6.4 & §10
 */
class RefreshDailyAggregatorJob implements ShouldQueue
{
    use Queueable;

    public function handle(RefreshDailyAggregatorAction $action): void
    {
        $action->execute();
    }
}
