<?php

declare(strict_types=1);

namespace App\Chat;

use App\Chat\Enums\LimitType;
use App\Chat\Enums\ModelType;
use App\Chat\Models\MessageLimitModel;
use App\Chat\Settings\ChatSettings;
use App\User\Models\UserModel;
use Illuminate\Support\Facades\DB;

class GrantDailyLimits
{
    private const GUEST_QUOTA = 5;

    /**
     * UPSERT dzienne limity dla pojedynczego usera. Idempotentne:
     *  - brak rekordu → insert z used=0, period_start=now()
     *  - rekord out-of-window (period_start < now - 1 day) → reset used=0, period_start=now()
     *  - rekord in-window → no-op (stan zachowany)
     *
     * Guest (email NULL): jednorazowy slot LimitType::Guest, quota=5, period_start=null.
     * Bez resetu — gdy zużyje, nic się nie odnawia.
     *
     * Nie dotyka pakietów (limit_type=package) — mają własny cykl życia.
     */
    public function forUser(UserModel $user): void
    {
        if ($user->isGuest()) {
            $this->grantGuestSlot($user);

            return;
        }

        $defaults = config('premium.daily', []);

        if ($defaults === []) {
            return;
        }

        DB::transaction(function () use ($user, $defaults): void {
            foreach ($defaults as $default) {
                $model = ModelType::from($default['model']);

                $existing = MessageLimitModel::query()
                    ->where('user_id', $user->id)
                    ->where('limit_type', LimitType::Daily->value)
                    ->where('model_type', $model->value)
                    ->lockForUpdate()
                    ->first();

                if ($existing === null) {
                    MessageLimitModel::create([
                        'user_id' => $user->id,
                        'model_type' => $model,
                        'limit_type' => LimitType::Daily,
                        'priority' => (int) $default['priority'],
                        'quota' => (int) $default['quota'],
                        'used' => 0,
                        'period_start' => now(),
                    ]);

                    continue;
                }

                $withinWindow = $existing->period_start?->gte(now()->subDay()) ?? false;

                if ($withinWindow) {
                    continue;
                }

                $existing->update([
                    'used' => 0,
                    'quota' => (int) $default['quota'],
                    'priority' => (int) $default['priority'],
                    'period_start' => now(),
                ]);
            }
        });
    }

    /**
     * Batch: iteruje po wszystkich userach (chunk), wywołuje forUser.
     * Używane przez nocny job RefreshDailyLimits. Skip ghostów — mają jednorazowy slot.
     */
    public function forAll(int $chunk = 100): void
    {
        UserModel::query()->registered()->chunkById($chunk, function ($users): void {
            foreach ($users as $user) {
                /** @var UserModel $user */
                $this->forUser($user);
            }
        });
    }

    private function grantGuestSlot(UserModel $user): void
    {
        $exists = MessageLimitModel::query()
            ->where('user_id', $user->id)
            ->where('limit_type', LimitType::Guest->value)
            ->exists();

        if ($exists) {
            return;
        }

        MessageLimitModel::create([
            'user_id' => $user->id,
            'model_type' => app(ChatSettings::class)->defaultModel,
            'limit_type' => LimitType::Guest,
            'priority' => 0,
            'quota' => self::GUEST_QUOTA,
            'used' => 0,
            'period_start' => null,
        ]);
    }
}
