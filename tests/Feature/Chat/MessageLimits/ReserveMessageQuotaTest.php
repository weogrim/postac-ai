<?php

declare(strict_types=1);

use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\Exceptions\OutOfMessagesException;
use App\Chat\Models\MessageLimitModel;
use App\Chat\ReserveMessageQuota;
use App\Chat\Settings\ChatSettings;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('premium user bypasses limits and gets ChatSettings default model', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->premium()->create();

    $model = app(ReserveMessageQuota::class)->reserve($user);

    expect($model)->toBe(app(ChatSettings::class)->defaultModel)
        ->and(MessageLimitModel::forUser($user)->count())->toBe(0);
});

it('grants daily limits on-demand for new free user', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $model = app(ReserveMessageQuota::class)->reserve($user);

    expect($model)->toBe(ModelType::Gpt4o);

    $gpt4o = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4o->value)->first();
    expect($gpt4o->used)->toBe(1);

    $gpt4oMini = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($gpt4oMini->used)->toBe(0);
});

it('falls through to lower priority when higher is exhausted', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

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
        'used' => 0,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $model = app(ReserveMessageQuota::class)->reserve($user);

    expect($model)->toBe(ModelType::Gpt4oMini);

    $mini = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($mini->used)->toBe(1);
});

it('throws OutOfMessages when all limits exhausted', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

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

    expect(fn () => app(ReserveMessageQuota::class)->reserve($user))
        ->toThrow(OutOfMessagesException::class);
});

it('treats out-of-window daily limits as unavailable (but auto-grants fresh ones)', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now()->subDays(3),
    ]);

    $model = app(ReserveMessageQuota::class)->reserve($user);

    $gpt4o = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4o->value)->first();
    expect($model)->toBe(ModelType::Gpt4o)
        ->and($gpt4o->used)->toBe(1)
        ->and($gpt4o->period_start->diffInMinutes(now()))->toBeLessThan(1);
});

it('prefers package limits (priority 3) over daily', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    MessageLimitModel::factory()->forUser($user)->package(quota: 10, model: ModelType::Gpt4o)->create();

    $model = app(ReserveMessageQuota::class)->reserve($user);

    expect($model)->toBe(ModelType::Gpt4o);
    $pkg = MessageLimitModel::forUser($user)->where('limit_type', LimitType::Package->value)->first();
    expect($pkg->used)->toBe(1);
});

it('increments atomically under repeated calls', function () {
    /** @var TestCase $this */
    config()->set('premium.daily', []);

    $user = UserModel::factory()->create();

    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 3,
        'used' => 0,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $rmq = app(ReserveMessageQuota::class);

    $rmq->reserve($user);
    $rmq->reserve($user);
    $rmq->reserve($user);

    expect(fn () => $rmq->reserve($user))->toThrow(OutOfMessagesException::class);

    $mini = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($mini->used)->toBe(3);
});
