<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('soft deletes related chats when character is soft deleted', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->create();
    $chat = Chat::factory()->recycle($user, $character)->create();

    $character->delete();

    expect($chat->fresh()->trashed())->toBeTrue();
    expect($character->fresh()->trashed())->toBeTrue();
});

it('restores related chats when character is restored', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->create();
    $chat = Chat::factory()->recycle($user, $character)->create();

    $character->delete();
    $character->fresh()->restore();

    expect($chat->fresh()->trashed())->toBeFalse();
});

it('hides cascaded chats from regular user chat list', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->create();
    Chat::factory()->recycle($user, $character)->create();

    $character->delete();

    expect(Chat::query()->where('user_id', $user->id)->count())->toBe(0);
    expect(Chat::withTrashed()->where('user_id', $user->id)->count())->toBe(1);
});
