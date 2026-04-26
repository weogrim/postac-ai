<?php

declare(strict_types=1);

namespace App\Billing\Controllers;

use Illuminate\View\View;

class BuyCancelController
{
    public function __invoke(): View
    {
        return view('buy.cancel');
    }
}
