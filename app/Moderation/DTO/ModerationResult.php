<?php

declare(strict_types=1);

namespace App\Moderation\DTO;

readonly class ModerationResult
{
    /**
     * @param  array<string, float>  $categories  per-category score (0..1)
     */
    public function __construct(
        public bool $flagged,
        public array $categories,
        public float $score,
    ) {}

    public function isSelfHarm(): bool
    {
        $threshold = 0.5;

        foreach ($this->categories as $category => $score) {
            if (str_contains($category, 'self-harm') && $score >= $threshold) {
                return true;
            }
        }

        return false;
    }

    public function topCategory(): ?string
    {
        if ($this->categories === []) {
            return null;
        }

        $max = 0.0;
        $top = null;

        foreach ($this->categories as $category => $score) {
            if ($score > $max) {
                $max = $score;
                $top = $category;
            }
        }

        return $top;
    }
}
