<?php

declare(strict_types=1);

namespace App\Chat\Jobs;

use App\Chat\GrantDailyLimits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshDailyLimits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GrantDailyLimits $grant): void
    {
        $grant->forAll();
    }
}
