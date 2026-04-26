<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\Models\MessageLimitModel;
use App\User\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageLimitModel>
 */
class MessageLimitFactory extends Factory
{
    protected $model = MessageLimitModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'model_type' => ModelType::Gpt4oMini,
            'limit_type' => LimitType::Daily,
            'priority' => 1,
            'quota' => 5,
            'used' => 0,
            'period_start' => now(),
        ];
    }

    public function forUser(UserModel $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function package(int $quota = 5, ModelType $model = ModelType::Gpt4o): static
    {
        return $this->state(fn () => [
            'limit_type' => LimitType::Package,
            'model_type' => $model,
            'quota' => $quota,
            'priority' => 3,
            'period_start' => null,
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $a) => [
            'used' => $a['quota'] ?? 5,
        ]);
    }
}
