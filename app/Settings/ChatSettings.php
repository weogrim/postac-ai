<?php

declare(strict_types=1);

namespace App\Settings;

use App\AI\ModelType;
use Spatie\LaravelSettings\Settings;

class ChatSettings extends Settings
{
    public ModelType $defaultModel;

    public int $historyLength;

    public string $beforeUserMessage;

    public string $afterUserMessage;

    public float $temperature;

    public int $maxTokens;

    public static function group(): string
    {
        return 'chat';
    }
}
