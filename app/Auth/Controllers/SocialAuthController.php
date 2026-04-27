<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\SocialProvider;
use App\User\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class SocialAuthController
{
    public function redirect(SocialProvider $provider): SymfonyRedirect
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider->value);

        /** @var SymfonyRedirect $redirect */
        $redirect = $driver->scopes($provider->scopes())->redirect();

        return $redirect;
    }

    public function callback(SocialProvider $provider): RedirectResponse
    {
        /** @var SocialiteUser $socialiteUser */
        $socialiteUser = Socialite::driver($provider->value)->user();

        $email = (string) $socialiteUser->getEmail();
        $current = Auth::user();
        $isGhost = $current instanceof UserModel && $current->isGuest();

        $existing = UserModel::query()
            ->whereNotNull('email')
            ->where('email', $email)
            ->first();

        if ($existing !== null) {
            if ($isGhost && $current->id !== $existing->id) {
                $current->delete();
            }

            if ($existing->email_verified_at === null) {
                $existing->forceFill(['email_verified_at' => now()])->save();
            }

            Auth::login($existing, remember: true);

            return $existing->birthdate === null
                ? redirect()->route('auth.complete')
                : redirect()->intended(route('home'));
        }

        $name = $socialiteUser->getName();
        $base = is_string($name) && $name !== '' ? $name : Str::before($email, '@');
        $candidate = Str::of($base)->trim()->substr(0, 40)->toString();

        if (UserModel::query()->where('name', $candidate)->where('id', '!=', $isGhost ? $current->id : 0)->exists()) {
            $candidate .= '-'.Str::lower(Str::random(4));
        }

        if ($isGhost) {
            $current->forceFill([
                'name' => $candidate,
                'email' => $email,
                'email_verified_at' => now(),
            ])->save();

            return redirect()->route('auth.complete');
        }

        $user = UserModel::create([
            'name' => $candidate,
            'email' => $email,
            'password' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        Auth::login($user, remember: true);

        return redirect()->route('auth.complete');
    }
}
