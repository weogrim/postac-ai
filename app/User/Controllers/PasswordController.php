<?php

declare(strict_types=1);

namespace App\User\Controllers;

use App\User\Requests\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController
{
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $user->forceFill([
            'password' => Hash::make((string) $request->string('password')),
        ])->save();

        return back()->with('status', 'Hasło zaktualizowane.');
    }
}
