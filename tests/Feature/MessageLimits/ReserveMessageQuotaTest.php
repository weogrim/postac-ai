<?php

declare(strict_types=1);

use App\Actions\ReserveMessageQuota;
use App\AI\ModelType;
use App\Exceptions\OutOfMessagesException;
use App\Models\MessageLimit;
use App\Models\User;
use App\Premium\LimitType;
use App\Settings\ChatSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('premium user bypasses limits and gets ChatSettings default model', function () {
    /** @var TestCase $this */
    $user = User::factory()->premium()->create();

    $model = app(ReserveMessageQuota::class)($user);

    expect($model)->toBe(app(ChatSettings::class)->defaultModel)
        ->and(MessageLimit::forUser($user)->count())->toBe(0);
});

it('grants daily limits on-demand for new free user', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $model = app(ReserveMessageQuota::class)($user);

    expect($model)->toBe(ModelType::Gpt4o);

    $gpt4o = MessageLimit::forUser($user)->where('model_type', ModelType::Gpt4o->value)->first();
    expect($gpt4o->used)->toBe(1);

    $gpt4oMini = MessageLimit::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($gpt4oMini->used)->toBe(0);
});

it('falls through to lower priority when higher is exhausted', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now(),
    ]);
    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 0,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $model = app(ReserveMessageQuota::class)($user);

    expect($model)->toBe(ModelType::Gpt4oMini);

    $mini = MessageLimit::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($mini->used)->toBe(1);
});

it('throws OutOfMessages when all limits exhausted', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now(),
    ]);
    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 20,
        'priority' => 1,
        'period_start' => now(),
    ]);

    expect(fn () => app(ReserveMessageQuota::class)($user))
        ->toThrow(OutOfMessagesException::class);
});

it('treats out-of-window daily limits as unavailable (but auto-grants fresh ones)', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4o,
        'limit_type' => LimitType::Daily,
        'quota' => 5,
        'used' => 5,
        'priority' => 2,
        'period_start' => now()->subDays(3),
    ]);

    $model = app(ReserveMessageQuota::class)($user);

    $gpt4o = MessageLimit::forUser($user)->where('model_type', ModelType::Gpt4o->value)->first();
    expect($model)->toBe(ModelType::Gpt4o)
        ->and($gpt4o->used)->toBe(1)
        ->and($gpt4o->period_start->diffInMinutes(now()))->toBeLessThan(1);
});

it('prefers package limits (priority 3) over daily', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    MessageLimit::factory()->forUser($user)->package(quota: 10, model: ModelType::Gpt4o)->create();

    $model = app(ReserveMessageQuota::class)($user);

    expect($model)->toBe(ModelType::Gpt4o);
    $pkg = MessageLimit::forUser($user)->where('limit_type', LimitType::Package->value)->first();
    expect($pkg->used)->toBe(1);
});

it('increments atomically under repeated calls', function () {
    /** @var TestCase $this */
    config()->set('premium.daily', []);

    $user = User::factory()->create();

    MessageLimit::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 3,
        'used' => 0,
        'priority' => 1,
        'period_start' => now(),
    ]);

    $reserve = app(ReserveMessageQuota::class);

    $reserve($user);
    $reserve($user);
    $reserve($user);

    expect(fn () => $reserve($user))->toThrow(OutOfMessagesException::class);

    $mini = MessageLimit::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($mini->used)->toBe(3);
});
