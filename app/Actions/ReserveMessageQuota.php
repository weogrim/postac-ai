<?php

declare(strict_types=1);

namespace App\Actions;

use App\AI\ModelType;
use App\Exceptions\OutOfMessagesException;
use App\Models\MessageLimit;
use App\Models\User;
use App\Settings\ChatSettings;
use Illuminate\Support\Facades\DB;

class ReserveMessageQuota
{
    public function __construct(
        private readonly GrantDailyLimits $grantDailyLimits,
        private readonly ChatSettings $chatSettings,
    ) {}

    /**
     * Atomowo wybiera i konsumuje jeden slot wiadomości dla usera.
     *
     * Premium: zwraca globalny ChatSettings::defaultModel bez dotykania DB.
     * Free: on-demand grant daily limits → lockForUpdate na wszystkich limitach
     * → pierwszy dostępny wg priority desc → increment `used` → zwraca model.
     * Jeśli nic dostępne → OutOfMessagesException.
     */
    public function __invoke(User $user): ModelType
    {
        if ($user->subscribed()) {
            return $this->chatSettings->defaultModel;
        }

        $this->grantDailyLimits->forUser($user);

        return DB::transaction(function () use ($user): ModelType {
            $limit = MessageLimit::query()
                ->forUser($user)
                ->forCurrentWindow()
                ->available()
                ->orderByDesc('priority')
                ->lockForUpdate()
                ->first();

            if ($limit === null) {
                throw new OutOfMessagesException;
            }

            $limit->increment('used');

            return $limit->model_type;
        });
    }
}
