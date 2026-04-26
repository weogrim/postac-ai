<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('injects sentry dsn meta tag when configured', function () {
    /** @var TestCase $this */
    config()->set('sentry.dsn', 'https://public@sentry.example.com/1');
    config()->set('sentry.environment', 'testing');

    $this->get(route('home'))
        ->assertSee('<meta name="sentry-dsn" content="https://public@sentry.example.com/1">', false)
        ->assertSee('<meta name="sentry-environment" content="testing">', false);
});

it('omits sentry meta tag when dsn is null', function () {
    /** @var TestCase $this */
    config()->set('sentry.dsn', null);

    $this->get(route('home'))->assertDontSee('sentry-dsn');
});
