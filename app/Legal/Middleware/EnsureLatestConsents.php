<?php

declare(strict_types=1);

namespace App\Legal\Middleware;

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLatestConsents
{
    public function handle(Request $request, Closure $next, string ...$slugs): Response
    {
        /** @var UserModel|null $user */
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        foreach ($slugs as $rawSlug) {
            $slug = DocumentSlug::tryFrom($rawSlug);

            if ($slug === null) {
                continue;
            }

            $latest = LegalDocumentModel::query()
                ->where('slug', $slug)
                ->whereNotNull('published_at')
                ->orderByDesc('version')
                ->first();

            if ($latest === null) {
                continue;
            }

            $hasConsent = ConsentModel::query()
                ->where('user_id', $user->id)
                ->where('legal_document_id', $latest->id)
                ->exists();

            if (! $hasConsent) {
                return redirect()->route('legal.show', ['slug' => $slug]);
            }
        }

        return $next($request);
    }
}
