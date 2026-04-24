<?php

declare(strict_types=1);

namespace App\Messaging;

enum SenderRole: string
{
    case User = 'user';
    case Character = 'character';

    public function bubbleSide(): string
    {
        return match ($this) {
            self::User => 'end',
            self::Character => 'start',
        };
    }
}
