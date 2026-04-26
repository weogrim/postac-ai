<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageModel>
 */
class MessageFactory extends Factory
{
    protected $model = MessageModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => ChatModel::factory(),
            'sender_role' => SenderRole::User,
            'user_id' => null,
            'character_id' => null,
            'content' => fake()->sentence(),
            'model' => null,
            'tokens_usage' => null,
        ];
    }

    public function fromUser(ChatModel $chat): static
    {
        return $this->state([
            'chat_id' => $chat->id,
            'sender_role' => SenderRole::User,
            'user_id' => $chat->user_id,
            'character_id' => null,
        ]);
    }

    public function fromCharacter(ChatModel $chat): static
    {
        return $this->state([
            'chat_id' => $chat->id,
            'sender_role' => SenderRole::Character,
            'user_id' => null,
            'character_id' => $chat->character_id,
        ]);
    }
}
