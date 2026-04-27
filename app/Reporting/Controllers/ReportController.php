<?php

declare(strict_types=1);

namespace App\Reporting\Controllers;

use App\Character\Models\CharacterModel;
use App\Chat\Models\MessageModel;
use App\Reporting\Enums\ReportStatus;
use App\Reporting\Models\ReportModel;
use App\Reporting\Requests\ReportRequest;
use App\User\EnsureGhostUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class ReportController
{
    public function store(ReportRequest $request): Response
    {
        $user = app(EnsureGhostUser::class)->forRequest($request);

        $key = 'report:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response('Zbyt wiele zgłoszeń. Spróbuj za chwilę.', 429);
        }

        $type = $request->string('reportable_type')->toString();
        $id = $request->string('reportable_id')->toString();

        $exists = match ($type) {
            'message' => MessageModel::query()->whereKey($id)->exists(),
            'character' => CharacterModel::query()->whereKey($id)->exists(),
            default => false,
        };

        abort_unless($exists, 404);

        ReportModel::create([
            'reporter_id' => $user->id,
            'reportable_type' => $type,
            'reportable_id' => $id,
            'reason' => $request->input('reason'),
            'description' => $request->input('description'),
            'status' => ReportStatus::Pending->value,
        ]);

        RateLimiter::hit($key, 60);

        if ($request->header('HX-Request') === 'true') {
            return response()
                ->view('htmx.report-thanks', [], 201)
                ->header('HX-Reswap', 'none');
        }

        return response('OK', 201);
    }
}
