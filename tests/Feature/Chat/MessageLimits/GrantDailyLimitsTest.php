<?php

declare(strict_types=1);

use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\GrantDailyLimits;
use App\Chat\Models\MessageLimitModel;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('grants defaults on first call', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    app(GrantDailyLimits::class)->forUser($user);

    $limits = MessageLimitModel::forUser($user)->get();
    expect($limits)->toHaveCount(2);

    $mini = $limits->firstWhere('model_type', ModelType::Gpt4oMini);
    expect($mini)->not->toBeNull()
        ->and($mini->limit_type)->toBe(LimitType::Daily)
        ->and($mini->quota)->toBe(20)
        ->and($mini->used)->toBe(0)
        ->and($mini->priority)->toBe(1);
});

it('is idempotent within current window', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    $grant = app(GrantDailyLimits::class);
    $grant->forUser($user);

    MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->increment('used', 3);

    $grant->forUser($user);

    $mini = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($mini->used)->toBe(3);
});

it('resets used and period_start when out of window', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    MessageLimitModel::factory()->forUser($user)->create([
        'model_type' => ModelType::Gpt4oMini,
        'limit_type' => LimitType::Daily,
        'quota' => 20,
        'used' => 18,
        'priority' => 1,
        'period_start' => now()->subDays(2),
    ]);

    app(GrantDailyLimits::class)->forUser($user);

    $mini = MessageLimitModel::forUser($user)->where('model_type', ModelType::Gpt4oMini->value)->first();
    expect($mini->used)->toBe(0)
        ->and($mini->period_start->diffInMinutes(now()))->toBeLessThan(1);
});

it('batches all users via forAll', function () {
    /** @var TestCase $this */
    $users = UserModel::factory()->count(3)->create();

    app(GrantDailyLimits::class)->forAll();

    foreach ($users as $user) {
        expect(MessageLimitModel::forUser($user)->count())->toBe(2);
    }
});

it('does not touch package limits', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create();

    MessageLimitModel::factory()->forUser($user)->package(quota: 10, model: ModelType::Gpt4o)->create([
        'used' => 4,
    ]);

    app(GrantDailyLimits::class)->forUser($user);

    $pkg = MessageLimitModel::forUser($user)->where('limit_type', LimitType::Package->value)->first();
    expect($pkg->used)->toBe(4)
        ->and($pkg->quota)->toBe(10);
});
