<?php

declare(strict_types=1);

namespace App\Home\Controllers;

use App\Character\Models\CharacterModel;
use Illuminate\Contracts\View\View;
use Spatie\Tags\Tag;

class HomeController
{
    public function index(): View
    {
        $popular = CharacterModel::query()
            ->regular()
            ->with(['author', 'media'])
            ->orderByDesc('is_official')
            ->orderByDesc('popularity_24h')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $latest = CharacterModel::query()
            ->regular()
            ->with(['author', 'media'])
            ->latest()
            ->limit(6)
            ->get();

        $categories = Tag::query()
            ->where('type', 'category')
            ->ordered()
            ->get();

        return view('home', [
            'popular' => $popular,
            'latest' => $latest,
            'categories' => $categories,
        ]);
    }
}
