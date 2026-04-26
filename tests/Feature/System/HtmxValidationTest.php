<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

beforeEach(function () {
    Route::post('/__test/validation', function (Request $request) {
        $request->validate(['name' => 'required']);

        return 'ok';
    })->middleware('web');
});

it('renders validation errors as an OOB toast for HTMX requests', function () {
    /** @var TestCase $this */
    $response = $this->withHeaders([
        'HX-Request' => 'true',
        'Accept' => 'text/html',
    ])->post('/__test/validation', []);

    $response->assertStatus(422);
    $response->assertHeader('HX-Reswap', 'none');
    $response->assertSee('id="toasts"', false);
    $response->assertSee('hx-swap-oob="beforeend"', false);
    $response->assertSee('role="alert"', false);
    $response->assertSee('alert-error', false);
    $response->assertSee('Wysłane dane są niepoprawne', false);
});

it('redirects back with errors for non-HTMX requests', function () {
    /** @var TestCase $this */
    $response = $this->from('/somewhere')->post('/__test/validation', []);

    $response->assertStatus(302);
    $response->assertRedirect('/somewhere');
    $response->assertSessionHasErrors('name');
});
