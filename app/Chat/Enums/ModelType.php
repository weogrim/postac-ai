<?php

declare(strict_types=1);

namespace App\Chat\Enums;

use Laravel\Ai\Enums\Lab;

enum ModelType: string
{
    case Gpt4o = 'openai/gpt-4o';
    case Gpt4oMini = 'openai/gpt-4o-mini';

    public function label(): string
    {
        return match ($this) {
            self::Gpt4o => 'GPT-4o',
            self::Gpt4oMini => 'GPT-4o mini',
        };
    }

    public function isPremium(): bool
    {
        return match ($this) {
            self::Gpt4o => true,
            self::Gpt4oMini => false,
        };
    }

    public function provider(): Lab
    {
        return Lab::OpenRouter;
    }
}
