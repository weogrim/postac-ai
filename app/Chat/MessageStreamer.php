<?php

declare(strict_types=1);

namespace App\Chat;

use App\Chat\Enums\ModelType;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\Chat\Settings\ChatSettings;
use Generator;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;

/**
 * Buduje historię konwersacji + uruchamia AnonymousAgent w trybie streamingu.
 * Yielduje eventy SSE jako tablice — kontroler je serializuje do `data: ...`.
 */
class MessageStreamer
{
    public function __construct(private readonly ChatSettings $settings) {}

    /**
     * @return Generator<int, array{delta?: string, tokens?: int}>
     */
    public function stream(ChatModel $chat, MessageModel $characterMessage): Generator
    {
        $chat->load('character');

        $conversation = $chat->messages()
            ->where('id', '!=', $characterMessage->id)
            ->where(fn ($q) => $q
                ->where('sender_role', SenderRole::User->value)
                ->orWhere(fn ($q) => $q
                    ->where('sender_role', SenderRole::Character->value)
                    ->where('content', '!=', '')))
            ->latest()
            ->take($this->settings->historyLength + 1)
            ->get()
            ->reverse()
            ->values();

        $lastUserMessage = $conversation->last();

        abort_if($lastUserMessage === null, 404, 'Brak wiadomości użytkownika do odpowiedzi.');

        $history = $conversation
            ->slice(0, -1)
            ->map(fn (MessageModel $m) => $m->sender_role === SenderRole::User
                ? new UserMessage($m->content)
                : new AssistantMessage($m->content))
            ->all();

        $latestUserContent = trim(
            $this->settings->beforeUserMessage.' '
            .$lastUserMessage->content
            .' '.$this->settings->afterUserMessage
        );

        $model = ModelType::from($characterMessage->model ?? ModelType::Gpt4oMini->value);

        $agent = new AnonymousAgent(
            instructions: $chat->character->prompt,
            messages: $history,
            tools: [],
        );

        $response = $agent->stream(
            prompt: $latestUserContent,
            provider: $model->provider(),
            model: $model->value,
        );

        foreach ($response as $event) {
            if ($event instanceof TextDelta) {
                yield ['delta' => $event->delta];
            }

            if ($event instanceof StreamEnd) {
                yield ['tokens' => $event->usage->completionTokens];
            }
        }
    }
}
