<?php

declare(strict_types=1);

namespace App\Auth;

enum SocialProvider: string
{
    case Google = 'google';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
        };
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return match ($this) {
            self::Google => ['openid', 'email', 'profile'],
        };
    }
}
