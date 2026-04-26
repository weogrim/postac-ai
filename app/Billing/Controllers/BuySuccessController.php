<?php

declare(strict_types=1);

namespace App\Billing\Controllers;

use Illuminate\View\View;

class BuySuccessController
{
    public function __invoke(): View
    {
        return view('buy.success');
    }
}
