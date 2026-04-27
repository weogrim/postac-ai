<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\User\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationNoticeController
{
    public function show(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user instanceof UserModel && $user->isGuest()) {
            return redirect()->route('register');
        }

        return $user?->hasVerifiedEmail() === true
            ? redirect()->intended(route('home'))
            : view('auth.verify-email');
    }
}
