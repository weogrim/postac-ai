<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationNoticeController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        return $request->user()?->hasVerifiedEmail() === true
            ? redirect()->intended(route('home'))
            : view('auth.verify-email');
    }
}
