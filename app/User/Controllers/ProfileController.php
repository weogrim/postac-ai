<?php

declare(strict_types=1);

namespace App\User\Controllers;

use App\User\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController
{
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'Profil zaktualizowany.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $request->validate([
            'confirm' => ['required', 'string', 'in:USUŃ'],
        ], attributes: ['confirm' => 'potwierdzenie']);

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Konto usunięte.');
    }

    public function limits(Request $request): View
    {
        $limits = $request->user()?->messageLimits()->get() ?? collect();

        return view('profile.limits', compact('limits'));
    }
}
