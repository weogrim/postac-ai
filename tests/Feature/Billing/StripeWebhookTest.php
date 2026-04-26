<?php

declare(strict_types=1);

use App\Billing\Controllers\StripeWebhookController;
use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\Models\MessageLimitModel;
use App\Chat\ReserveMessageQuota;
use App\User\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $priceIds
 */
function fakeWebhookController(array $priceIds): StripeWebhookController
{
    return new class($priceIds) extends StripeWebhookController
    {
        /**
         * @param  list<string>  $priceIds
         */
        public function __construct(private readonly array $priceIds) {}

        protected function lineItemsForSession(string $sessionId): array
        {
            return $this->priceIds;
        }
    };
}

/**
 * @return array<string, mixed>
 */
function checkoutSessionPayload(string $customerId): array
{
    return [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_'.uniqid(),
                'customer' => $customerId,
            ],
        ],
    ];
}

it('creates MessageLimit for Five package via webhook', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_five']);

    $controller = fakeWebhookController([config('billing.prices.five')]);

    $controller->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_five'));

    $limit = MessageLimitModel::forUser($user)->first();
    expect($limit)->not->toBeNull()
        ->and($limit->limit_type)->toBe(LimitType::Package)
        ->and($limit->quota)->toBe(130)
        ->and($limit->priority)->toBe(3)
        ->and($limit->model_type)->toBe(ModelType::Gpt4o)
        ->and($limit->used)->toBe(0)
        ->and($limit->period_start)->toBeNull();
});

it('creates MessageLimit for Ten package with quota 270', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_ten']);

    fakeWebhookController([config('billing.prices.ten')])
        ->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_ten'));

    expect(MessageLimitModel::forUser($user)->first()->quota)->toBe(270);
});

it('creates MessageLimit for Fifteen package with quota 400', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_fifteen']);

    fakeWebhookController([config('billing.prices.fifteen')])
        ->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_fifteen'));

    expect(MessageLimitModel::forUser($user)->first()->quota)->toBe(400);
});

it('does not create MessageLimit for Premium subscription', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_premium']);

    fakeWebhookController([config('billing.prices.premium')])
        ->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_premium'));

    expect(MessageLimitModel::forUser($user)->count())->toBe(0);
});

it('handles multiple line items in single checkout', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_multi']);

    fakeWebhookController([
        config('billing.prices.five'),
        config('billing.prices.ten'),
    ])->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_multi'));

    $limits = MessageLimitModel::forUser($user)->orderBy('quota')->get();
    expect($limits)->toHaveCount(2)
        ->and($limits[0]->quota)->toBe(130)
        ->and($limits[1]->quota)->toBe(270);
});

it('skips unknown price id without aborting webhook', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_unknown']);

    fakeWebhookController([
        'price_unknown_xxx',
        config('billing.prices.five'),
    ])->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_unknown'));

    $limits = MessageLimitModel::forUser($user)->get();
    expect($limits)->toHaveCount(1)
        ->and($limits[0]->quota)->toBe(130);
});

it('returns success when customer is not in DB', function () {
    /** @var TestCase $this */
    $response = fakeWebhookController([config('billing.prices.five')])
        ->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_ghost'));

    expect($response->getStatusCode())->toBe(200)
        ->and(MessageLimitModel::count())->toBe(0);
});

it('package limits interact with ReserveMessageQuota priority 3 > daily', function () {
    /** @var TestCase $this */
    $user = UserModel::factory()->create(['stripe_id' => 'cus_prio']);

    fakeWebhookController([config('billing.prices.five')])
        ->handleCheckoutSessionCompleted(checkoutSessionPayload('cus_prio'));

    $model = app(ReserveMessageQuota::class)($user);

    expect($model)->toBe(ModelType::Gpt4o);

    $pkg = MessageLimitModel::forUser($user)->where('limit_type', LimitType::Package->value)->first();
    expect($pkg->used)->toBe(1);
});
