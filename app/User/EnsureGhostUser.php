<?php

declare(strict_types=1);

namespace App\User;

use App\User\Models\UserModel;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class EnsureGhostUser
{
    private const RATE_LIMIT = 5;

    private const DECAY_SECONDS = 60;

    /**
     * Zwraca aktualnie zalogowanego usera lub tworzy "ghost" rekord
     * (email NULL, password NULL) i loguje go w bieżącej sesji.
     *
     * IP rate limit 5/min — bot robiący spam → ThrottleRequestsException (429).
     * Realnie człowiek nie powinien stworzyć więcej niż jednego ghosta na sesję.
     */
    public function forRequest(Request $request): UserModel
    {
        if (Auth::check()) {
            /** @var UserModel $user */
            $user = Auth::user();

            return $user;
        }

        $key = 'ghost:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT)) {
            throw new ThrottleRequestsException('Zwolnij trochę. Spróbuj za minutę.');
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $user = UserModel::create([
            'name' => 'Gość',
            'email' => null,
            'password' => null,
            'birthdate' => null,
        ]);

        Auth::login($user, remember: true);

        return $user;
    }
}
