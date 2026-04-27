<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Dating\Models\DatingProfileModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatingProfileModel>
 */
class DatingProfileFactory extends Factory
{
    protected $model = DatingProfileModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'character_id' => CharacterModel::factory()->state([
                'kind' => CharacterKind::Dating,
                'is_official' => true,
            ]),
            'age' => fake()->numberBetween(20, 35),
            'city' => fake()->randomElement(['Warszawa', 'Kraków', 'Wrocław', 'Gdańsk', 'Poznań']),
            'bio' => fake()->paragraph(2),
            'interests' => fake()->randomElements(
                ['kawa', 'fotografia', 'górskie szlaki', 'kino', 'koty', 'książki', 'muzyka', 'gry'],
                3,
            ),
            'accent_color' => fake()->randomElement(['#ff5d8f', '#7c3aed', '#0ea5e9', '#22c55e']),
        ];
    }
}
