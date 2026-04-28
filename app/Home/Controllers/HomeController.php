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
            ->with(['author', 'media', 'tags'])
            ->orderByDesc('is_official')
            ->orderByDesc('popularity_24h')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $latest = CharacterModel::query()
            ->regular()
            ->with(['author', 'media', 'tags'])
            ->latest()
            ->limit(6)
            ->get();

        $categories = Tag::query()
            ->where('type', 'category')
            ->ordered()
            ->get();

        $rotatingNames = $popular->pluck('name')->all();

        $marqueeNames = CharacterModel::query()
            ->regular()
            ->orderByDesc('popularity_24h')
            ->limit(12)
            ->pluck('name')
            ->all();

        $totalCharacters = CharacterModel::query()->regular()->count();

        return view('home', [
            'popular' => $popular,
            'latest' => $latest,
            'categories' => $categories,
            'rotatingNames' => $rotatingNames,
            'marqueeNames' => $marqueeNames,
            'totalCharacters' => $totalCharacters,
        ]);
    }
}
