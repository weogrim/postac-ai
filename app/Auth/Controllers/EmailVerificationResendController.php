<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationResendController
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail() === true) {
            return redirect()->intended(route('home'));
        }

        $request->user()?->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
