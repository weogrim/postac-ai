<?php

declare(strict_types=1);

use App\Character\Models\CharacterModel;
use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @return array{0: UserModel, 1: ChatModel}
 */
function seedChatForQuota(): array
{
    $user = UserModel::factory()->create();
    $character = CharacterModel::factory()->create(['user_id' => $user->id]);
    $chat = ChatModel::factory()->create(['user_id' => $user->id, 'character_id' => $character->id]);

    return [$user, $chat];
}

it('store succeeds when user has fresh limits and consumes one slot', function () {
    /** @var TestCase $this */
    [$user, $chat] = seedChatForQuota();

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Cześć!']);

    $response->assertCreated();

    expect(MessageLimitModel::forUser($user)->sum('used'))->toBe(1);

    $charMsg = $chat->messages()->where('sender_role', SenderRole::Character->value)->first();
    expect($charMsg->model)->toBe(ModelType::Gpt4o->value);
});

it('store returns 403 when user is out of messages', function () {
    /** @var TestCase $this */
    [$user, $chat] = seedChatForQuota();

    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now(),
    ]);
    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 20,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Hej']);

    $response->assertForbidden();
    expect($chat->messages()->count())->toBe(0);
});

it('store returns HTMX toast + 403 + HX-Reswap none when OOM and HX-Request', function () {
    /** @var TestCase $this */
    [$user, $chat] = seedChatForQuota();

    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now(),
    ]);
    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 20,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('HX-Request', 'true')
        ->post("/chat/{$chat->id}/messages", ['content' => 'Hej']);

    $response->assertForbidden();
    $response->assertHeader('HX-Reswap', 'none');
    $response->assertSee('Limit wiadomości wyczerpany');
    $response->assertSee('hx-swap-oob', false);
});

it('store uses ChatSettings default model for premium users (no MessageLimit mutation)', function () {
    /** @var TestCase $this */
    [, $chat] = seedChatForQuota();
    $user = UserModel::factory()->premium()->create();
    $chat->update(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post("/chat/{$chat->id}/messages", ['content' => 'Cześć!']);

    $response->assertCreated();
    expect(MessageLimitModel::forUser($user)->count())->toBe(0);
});
