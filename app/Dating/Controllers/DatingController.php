<?php

declare(strict_types=1);

namespace App\Dating\Controllers;

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Dating\HasAcceptedDatingTerms;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatingController
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->isGuest() && ! app(HasAcceptedDatingTerms::class)->check($user)) {
            return redirect()->route('dating.onboarding');
        }

        $profiles = CharacterModel::query()
            ->dating()
            ->whereNull('deleted_at')
            ->with(['datingProfile', 'media'])
            ->whereHas('datingProfile')
            ->orderByDesc('popularity_24h')
            ->orderByDesc('created_at')
            ->paginate(24);

        return view('dating.index', [
            'profiles' => $profiles,
        ]);
    }

    public function show(Request $request, CharacterModel $character): View|RedirectResponse
    {
        abort_unless($character->kind === CharacterKind::Dating, 404);

        $user = $request->user();

        if ($user !== null && ! $user->isGuest() && ! app(HasAcceptedDatingTerms::class)->check($user)) {
            return redirect()->route('dating.onboarding');
        }

        $character->load(['datingProfile', 'media']);

        abort_if($character->datingProfile === null, 404);

        return view('dating.show', [
            'character' => $character,
        ]);
    }
}
