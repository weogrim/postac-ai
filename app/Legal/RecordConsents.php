<?php

declare(strict_types=1);

namespace App\Legal;

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;
use Illuminate\Http\Request;

class RecordConsents
{
    /**
     * @param  list<DocumentSlug>  $slugs
     */
    public function record(UserModel $user, array $slugs, Request $request): void
    {
        foreach ($slugs as $slug) {
            $document = LegalDocumentModel::query()
                ->where('slug', $slug)
                ->whereNotNull('published_at')
                ->orderByDesc('version')
                ->first();

            if ($document === null) {
                continue;
            }

            ConsentModel::create([
                'user_id' => $user->id,
                'legal_document_id' => $document->id,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}
