<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Requests\RegisterRequest;
use App\Legal\Enums\DocumentSlug;
use App\Legal\RecordConsents;
use App\User\Models\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $current = $request->user();

        if ($current instanceof UserModel && $current->isGuest()) {
            $email = (string) $request->string('email');

            $existing = UserModel::query()
                ->whereNotNull('email')
                ->where('email', $email)
                ->where('id', '!=', $current->id)
                ->first();

            if ($existing !== null) {
                $current->delete();
                Auth::login($existing);

                return redirect()->route('home')
                    ->with('status', 'To konto już istnieje — zostałeś zalogowany.');
            }

            $current->forceFill([
                'name' => (string) $request->string('name'),
                'email' => $email,
                'password' => Hash::make((string) $request->string('password')),
                'birthdate' => $request->date('birthdate'),
            ])->save();

            app(RecordConsents::class)->record($current, [DocumentSlug::Terms, DocumentSlug::Privacy], $request);

            event(new Registered($current));

            return redirect()->route('verification.notice');
        }

        $user = UserModel::create($request->safe()->only([
            'name', 'email', 'password', 'birthdate',
        ]));

        app(RecordConsents::class)->record($user, [DocumentSlug::Terms, DocumentSlug::Privacy], $request);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
