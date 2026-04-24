<?php

declare(strict_types=1);

return [
    'prices' => [
        'five' => env('STRIPE_PRICE_FIVE'),
        'ten' => env('STRIPE_PRICE_TEN'),
        'fifteen' => env('STRIPE_PRICE_FIFTEEN'),
        'premium' => env('STRIPE_PRICE_PREMIUM'),
    ],
];
