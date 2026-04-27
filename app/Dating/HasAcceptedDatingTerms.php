<?php

declare(strict_types=1);

namespace App\Dating;

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\ConsentModel;
use App\Legal\Models\LegalDocumentModel;
use App\User\Models\UserModel;

class HasAcceptedDatingTerms
{
    public function check(UserModel $user): bool
    {
        $document = LegalDocumentModel::query()
            ->where('slug', DocumentSlug::DatingTerms)
            ->whereNotNull('published_at')
            ->orderByDesc('version')
            ->first();

        if ($document === null) {
            return true;
        }

        return ConsentModel::query()
            ->where('user_id', $user->id)
            ->where('legal_document_id', $document->id)
            ->exists();
    }
}
