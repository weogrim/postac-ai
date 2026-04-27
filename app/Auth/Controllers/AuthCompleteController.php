<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\Requests\AuthCompleteRequest;
use App\Legal\Enums\DocumentSlug;
use App\Legal\RecordConsents;
use App\User\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthCompleteController
{
    public function show(): View|RedirectResponse
    {
        /** @var UserModel $user */
        $user = auth()->user();

        if ($user->birthdate !== null) {
            return redirect()->intended(route('home'));
        }

        return view('auth.complete');
    }

    public function store(AuthCompleteRequest $request): RedirectResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        $user->forceFill(['birthdate' => $request->date('birthdate')])->save();

        app(RecordConsents::class)->record($user, [DocumentSlug::Terms, DocumentSlug::Privacy], $request);

        return redirect()->intended(route('home'));
    }
}
