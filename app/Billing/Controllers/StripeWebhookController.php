<?php

declare(strict_types=1);

namespace App\Billing\Controllers;

use App\Billing\Package;
use App\Chat\Enums\LimitType;
use App\Chat\Models\MessageLimitModel;
use App\User\Models\UserModel;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends WebhookController
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleCheckoutSessionCompleted(array $payload): Response
    {
        $data = $payload['data']['object'];
        $user = $this->getUserByStripeId($data['customer'] ?? null);

        if ($user === null) {
            Log::warning('checkout.session.completed: no user for stripe customer', [
                'customer' => $data['customer'] ?? null,
            ]);

            return $this->successMethod();
        }

        foreach ($this->lineItemsForSession($data['id']) as $priceId) {
            try {
                $package = Package::fromPriceId($priceId);
            } catch (Throwable) {
                Log::error('checkout.session.completed: unknown price id', [
                    'price_id' => $priceId,
                    'user_id' => $user->id,
                ]);

                continue;
            }

            if ($package->isSubscription()) {
                continue;
            }

            $limit = MessageLimitModel::create([
                'user_id' => $user->id,
                'model_type' => $package->model(),
                'limit_type' => LimitType::Package,
                'priority' => $package->priority(),
                'quota' => $package->messageLimit(),
                'used' => 0,
                'period_start' => null,
            ]);

            Log::info('checkout.session.completed: message limit granted', [
                'user_id' => $user->id,
                'package' => $package->value,
                'limit_id' => $limit->id,
            ]);
        }

        return $this->successMethod();
    }

    protected function getUserByStripeId($stripeId): ?UserModel
    {
        /** @var UserModel|null $user */
        $user = parent::getUserByStripeId($stripeId);

        return $user;
    }

    /**
     * @return array<int, string>
     */
    protected function lineItemsForSession(string $sessionId): array
    {
        $response = Cashier::stripe()->checkout->sessions->allLineItems($sessionId);

        return array_map(
            fn (object $item): string => $item->price->id,
            $response->data,
        );
    }
}
