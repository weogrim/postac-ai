<?php

declare(strict_types=1);

namespace App\Moderation\Contracts;

use App\Moderation\DTO\ModerationResult;

interface ModerationProvider
{
    public function check(string $text): ModerationResult;
}
