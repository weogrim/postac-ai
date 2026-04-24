<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $user->forceFill([
            'password' => Hash::make((string) $request->string('password')),
        ])->save();

        return back()->with('status', 'Hasło zaktualizowane.');
    }
}
