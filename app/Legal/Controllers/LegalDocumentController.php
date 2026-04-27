<?php

declare(strict_types=1);

namespace App\Legal\Controllers;

use App\Legal\Enums\DocumentSlug;
use App\Legal\Models\LegalDocumentModel;
use Illuminate\View\View;
use League\CommonMark\CommonMarkConverter;

class LegalDocumentController
{
    public function show(DocumentSlug $slug): View
    {
        $document = LegalDocumentModel::query()
            ->where('slug', $slug)
            ->whereNotNull('published_at')
            ->orderByDesc('version')
            ->firstOrFail();

        $rendered = (new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]))->convert($document->content)->getContent();

        return view('legal.show', [
            'document' => $document,
            'rendered' => $rendered,
        ]);
    }
}
