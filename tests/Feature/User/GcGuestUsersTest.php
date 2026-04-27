<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('deletes ghost users older than threshold without recent messages', function () {
    /** @var TestCase $this */
    $stale = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
    ]);
    $stale->forceFill(['created_at' => now()->subDays(10)])->save();

    $this->artisan('users:gc-guests', ['--inactive-days' => 7])->assertExitCode(0);

    expect(UserModel::query()->find($stale->id))->toBeNull();
});

it('keeps active ghost users with recent messages', function () {
    /** @var TestCase $this */
    $active = UserModel::factory()->create([
        'email' => null,
        'password' => null,
        'birthdate' => null,
        'email_verified_at' => null,
    ]);
    $active->forceFill(['created_at' => now()->subDays(10)])->save();

    $character = CharacterModel::factory()->create();
    $chat = ChatModel::create([
        'user_id' => $active->id,
        'character_id' => $character->id,
    ]);
    MessageModel::create([
        'chat_id' => $chat->id,
        'sender_role' => SenderRole::User,
        'user_id' => $active->id,
        'content' => 'cześć',
    ]);

    $this->artisan('users:gc-guests', ['--inactive-days' => 7])->assertExitCode(0);

    expect(UserModel::query()->find($active->id))->not->toBeNull();
});

it('does not touch registered users', function () {
    /** @var TestCase $this */
    $registered = UserModel::factory()->create();
    $registered->forceFill(['created_at' => now()->subYears(2)])->save();

    $this->artisan('users:gc-guests', ['--inactive-days' => 1])->assertExitCode(0);

    expect(UserModel::query()->find($registered->id))->not->toBeNull();
});
