<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('soft deletes related chats when character is soft deleted', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->recycle($user)->create();
    $chat = ChatModel::factory()->recycle($user, $character)->create();

    $character->delete();

    expect($chat->fresh()->trashed())->toBeTrue();
    expect($character->fresh()->trashed())->toBeTrue();
});

it('restores related chats when character is restored', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->recycle($user)->create();
    $chat = ChatModel::factory()->recycle($user, $character)->create();

    $character->delete();
    $character->fresh()->restore();

    expect($chat->fresh()->trashed())->toBeFalse();
});

it('hides cascaded chats from regular user chat list', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->recycle($user)->create();
    ChatModel::factory()->recycle($user, $character)->create();

    $character->delete();

    expect(ChatModel::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(ChatModel::withTrashed()->where('user_id', $user->id)->count())->toBe(1);
});
