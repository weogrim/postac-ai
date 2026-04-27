<?php

declare(strict_types=1);

return [

    'default' => env('MODERATION_PROVIDER', 'openai'),

    'providers' => [

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY', ''),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODERATION_MODEL', 'omni-moderation-latest'),
            'timeout' => (float) env('OPENAI_MODERATION_TIMEOUT', 3.0),
        ],

        'noop' => [
            'driver' => 'noop',
        ],

    ],

    'self_harm' => [
        'rate_limit' => (int) env('MODERATION_SELFHARM_LIMIT', 3),
        'window_seconds' => (int) env('MODERATION_SELFHARM_WINDOW', 300),
    ],

];
