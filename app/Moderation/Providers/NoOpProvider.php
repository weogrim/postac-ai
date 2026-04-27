<?php

declare(strict_types=1);

namespace App\Moderation\Providers;

use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\DTO\ModerationResult;

class NoOpProvider implements ModerationProvider
{
    public function check(string $text): ModerationResult
    {
        return new ModerationResult(flagged: false, categories: [], score: 0.0);
    }
}
