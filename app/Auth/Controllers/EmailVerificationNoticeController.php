<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationNoticeController
{
    public function show(Request $request): RedirectResponse|View
    {
        return $request->user()?->hasVerifiedEmail() === true
            ? redirect()->intended(route('home'))
            : view('auth.verify-email');
    }
}
