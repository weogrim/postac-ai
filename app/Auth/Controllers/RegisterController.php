<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Requests\RegisterRequest;
use App\User\Models\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = UserModel::create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
