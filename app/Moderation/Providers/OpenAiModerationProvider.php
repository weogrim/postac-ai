<?php

declare(strict_types=1);

namespace App\Moderation\Providers;

use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\DTO\ModerationResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiModerationProvider implements ModerationProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly float $timeoutSeconds,
    ) {}

    public function check(string $text): ModerationResult
    {
        if (trim($text) === '' || $this->apiKey === '') {
            return new ModerationResult(flagged: false, categories: [], score: 0.0);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeoutSeconds)
                ->post(rtrim($this->baseUrl, '/').'/moderations', [
                    'model' => $this->model,
                    'input' => $text,
                ]);
        } catch (Throwable $e) {
            report($e);

            return new ModerationResult(flagged: false, categories: [], score: 0.0);
        }

        if (! $response->successful()) {
            report(new RuntimeException('OpenAI moderation HTTP '.$response->status().': '.$response->body()));

            return new ModerationResult(flagged: false, categories: [], score: 0.0);
        }

        $result = $response->json('results.0');

        if (! is_array($result)) {
            return new ModerationResult(flagged: false, categories: [], score: 0.0);
        }

        $flagged = (bool) ($result['flagged'] ?? false);
        $rawScores = $result['category_scores'] ?? [];
        $categories = [];
        $maxScore = 0.0;

        if (is_array($rawScores)) {
            foreach ($rawScores as $name => $score) {
                if (! is_string($name) || ! is_numeric($score)) {
                    continue;
                }
                $value = (float) $score;
                $categories[$name] = $value;
                if ($value > $maxScore) {
                    $maxScore = $value;
                }
            }
        }

        return new ModerationResult(
            flagged: $flagged,
            categories: $categories,
            score: $maxScore,
        );
    }
}
