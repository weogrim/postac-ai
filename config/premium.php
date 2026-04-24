<?php

declare(strict_types=1);

use App\AI\ModelType;

return [
    /*
     * Domyślne dzienne limity wiadomości dla użytkowników free.
     * Wyższy priority wygrywa przy wyborze modelu (GPT-4o powyżej GPT-4o mini).
     * UPSERT-owane przez App\Actions\GrantDailyLimits — on-demand przy pierwszej
     * wiadomości usera oraz nocny job RefreshDailyLimits.
     */
    'daily' => [
        [
            'model' => ModelType::Gpt4oMini->value,
            'quota' => 20,
            'priority' => 1,
        ],
        [
            'model' => ModelType::Gpt4o->value,
            'quota' => 5,
            'priority' => 2,
        ],
    ],
];
