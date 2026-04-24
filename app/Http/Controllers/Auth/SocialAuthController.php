<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\SocialProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class SocialAuthController extends Controller
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

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $this->buildName($socialiteUser, $email),
                'password' => null,
                'email_verified_at' => now(),
            ],
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    private function buildName(SocialiteUser $socialiteUser, string $email): string
    {
        $base = $socialiteUser->getName() ?? Str::before($email, '@');
        $candidate = Str::of($base)->trim()->substr(0, 40)->toString();

        return User::where('name', $candidate)->exists()
            ? $candidate.'-'.Str::lower(Str::random(4))
            : $candidate;
    }
}
