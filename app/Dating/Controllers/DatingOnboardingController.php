<?php

declare(strict_types=1);

namespace App\Dating\Controllers;

use App\Dating\HasAcceptedDatingTerms;
use App\Dating\Requests\DatingOnboardingRequest;
use App\Legal\Enums\DocumentSlug;
use App\Legal\RecordConsents;
use App\User\Models\UserModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatingOnboardingController
{
    public function show(Request $request): View|RedirectResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        if (app(HasAcceptedDatingTerms::class)->check($user)) {
            return redirect()->route('dating.index');
        }

        return view('dating.onboarding');
    }

    public function store(DatingOnboardingRequest $request): RedirectResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        app(RecordConsents::class)->record($user, [DocumentSlug::DatingTerms], $request);

        return redirect()->route('dating.index');
    }
}
