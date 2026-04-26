<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatModel>
 */
class ChatFactory extends Factory
{
    protected $model = ChatModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'character_id' => CharacterModel::factory(),
        ];
    }
}
