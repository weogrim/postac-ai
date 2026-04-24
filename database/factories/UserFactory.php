<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Active Cashier subscription — before Faza 7 wires webhooks,
     * tests need a way to simulate a premium user.
     */
    public function premium(): static
    {
        return $this->afterCreating(function (User $user): void {
            Subscription::create([
                'user_id' => $user->id,
                'type' => 'default',
                'stripe_id' => 'sub_'.Str::random(14),
                'stripe_status' => 'active',
                'stripe_price' => 'price_premium_test',
                'quantity' => 1,
            ]);
        });
    }
}
