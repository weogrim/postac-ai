<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationResendController extends Controller
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
