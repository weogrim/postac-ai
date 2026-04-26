<?php

declare(strict_types=1);

namespace App\Billing\Controllers;

use App\Billing\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Cashier\Checkout;

class BuyController
{
    public function index(): View
    {
        return view('buy.index', [
            'packages' => Package::cases(),
        ]);
    }

    public function store(Package $package, Request $request): Checkout|RedirectResponse
    {
        $user = $request->user();

        $callbacks = [
            'success_url' => route('buy.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('buy.cancel'),
        ];

        if ($package->isSubscription()) {
            return $user
                ->newSubscription('default', $package->priceId())
                ->checkout($callbacks);
        }

        return $user->checkout([$package->priceId() => 1], $callbacks);
    }
}
