<?php

declare(strict_types=1);

use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\Providers\NoOpProvider;

it('NoOp provider always returns flagged=false', function () {
    $provider = new NoOpProvider;

    $result = $provider->check('any text here');

    expect($result->flagged)->toBeFalse();
    expect($result->categories)->toBe([]);
    expect($result->score)->toBe(0.0);
    expect($result->isSelfHarm())->toBeFalse();
});

it('default test environment binds NoOp provider', function () {
    expect(app(ModerationProvider::class))->toBeInstanceOf(NoOpProvider::class);
});
