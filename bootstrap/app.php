<?php

declare(strict_types=1);

use App\Exceptions\OutOfMessagesException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->header('HX-Request') !== 'true') {
                return null;
            }

            return response()
                ->view('htmx.validation-errors', [
                    'errors' => Arr::flatten($e->errors()),
                ], 422)
                ->header('HX-Reswap', 'none');
        });

        $exceptions->render(function (OutOfMessagesException $e, Request $request) {
            if ($request->header('HX-Request') === 'true') {
                return response()
                    ->view('htmx.out-of-messages', ['message' => $e->getMessage()], 403)
                    ->header('HX-Reswap', 'none');
            }

            return response()->view('errors.out-of-messages', ['message' => $e->getMessage()], 403);
        });
    })->create();
