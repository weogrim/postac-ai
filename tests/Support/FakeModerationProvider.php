<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\DTO\ModerationResult;

class FakeModerationProvider implements ModerationProvider
{
    /**
     * @param  array<string, float>  $categories
     */
    public function __construct(
        public bool $flagged = false,
        public array $categories = [],
        public ?float $score = null,
    ) {}

    public function check(string $text): ModerationResult
    {
        $score = $this->score ?? (max([0.0, ...array_values($this->categories)]));

        return new ModerationResult(
            flagged: $this->flagged,
            categories: $this->categories,
            score: $score,
        );
    }
}
