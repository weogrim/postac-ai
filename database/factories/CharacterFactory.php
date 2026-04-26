<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Character\Models\CharacterModel;
use App\User\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Mediable\Facades\ImageManipulator;
use Plank\Mediable\Facades\MediaUploader;

/**
 * @extends Factory<CharacterModel>
 */
class CharacterFactory extends Factory
{
    protected $model = CharacterModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'name' => fake()->name(),
            'prompt' => fake()->paragraph(),
        ];
    }

    public function withAvatar(): static
    {
        return $this->afterCreating(function (CharacterModel $character): void {
            $tmp = tempnam(sys_get_temp_dir(), 'avatar_').'.png';

            $img = imagecreatetruecolor(512, 512);
            $bg = imagecolorallocate($img, random_int(60, 200), random_int(60, 200), random_int(60, 200));
            imagefill($img, 0, 0, $bg);
            imagepng($img, $tmp);
            imagedestroy($img);

            $media = MediaUploader::fromSource($tmp)
                ->toDestination('public', 'characters')
                ->useHashForFilename()
                ->upload();

            ImageManipulator::createImageVariant($media, 'square');

            $character->attachMedia($media, 'avatar');

            @unlink($tmp);
        });
    }
}
