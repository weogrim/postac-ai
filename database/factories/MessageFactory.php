<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Messaging\SenderRole;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'sender_role' => SenderRole::User,
            'user_id' => null,
            'character_id' => null,
            'content' => fake()->sentence(),
            'model' => null,
            'tokens_usage' => null,
        ];
    }

    public function fromUser(Chat $chat): static
    {
        return $this->state([
            'chat_id' => $chat->id,
            'sender_role' => SenderRole::User,
            'user_id' => $chat->user_id,
            'character_id' => null,
        ]);
    }

    public function fromCharacter(Chat $chat): static
    {
        return $this->state([
            'chat_id' => $chat->id,
            'sender_role' => SenderRole::Character,
            'user_id' => null,
            'character_id' => $chat->character_id,
        ]);
    }
}
