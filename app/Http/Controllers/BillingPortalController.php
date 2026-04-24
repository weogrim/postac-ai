<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->hasStripeId(), 404);

        return $user->redirectToBillingPortal(route('profile.show'));
    }
}
