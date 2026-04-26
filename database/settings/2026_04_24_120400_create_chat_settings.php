<?php

declare(strict_types=1);

use App\Chat\Enums\ModelType;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('chat.defaultModel', ModelType::Gpt4oMini->value);
        $this->migrator->add('chat.historyLength', 20);
        $this->migrator->add('chat.beforeUserMessage', '');
        $this->migrator->add('chat.afterUserMessage', '');
        $this->migrator->add('chat.temperature', 0.9);
        $this->migrator->add('chat.maxTokens', 1024);
    }
};
