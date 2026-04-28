<?php

declare(strict_types=1);

namespace App\Character\Controllers;

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Character\Requests\CharacterStoreRequest;
use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plank\Mediable\Facades\ImageManipulator;
use Plank\Mediable\Facades\MediaUploader;
use Spatie\Tags\Tag;

class CharacterController
{
    public function index(Request $request): View
    {
        $characters = $this->buildBrowseQuery($request)
            ->with(['categories', 'media', 'tags'])
            ->paginate(24)
            ->withQueryString();

        $categories = Tag::query()
            ->where('type', 'category')
            ->ordered()
            ->get();

        return view('characters.index', [
            'characters' => $characters,
            'categories' => $categories,
            'totalCharacters' => CharacterModel::query()->regular()->count(),
            'q' => $request->string('q')->toString(),
            'category' => $request->string('category')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'popular',
            'official' => $request->boolean('official'),
        ]);
    }

    public function search(Request $request): View
    {
        $characters = $this->buildBrowseQuery($request)
            ->with(['categories', 'media', 'tags'])
            ->limit(24)
            ->get();

        return view('characters._grid', [
            'characters' => $characters,
        ]);
    }

    public function show(CharacterModel $character): View
    {
        abort_if($character->kind === CharacterKind::Dating, 404);

        $character->load(['author', 'categories', 'freeTags', 'media']);

        return view('characters.show', [
            'character' => $character,
        ]);
    }

    public function create(): View
    {
        return view('characters.create');
    }

    public function store(CharacterStoreRequest $request): RedirectResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

        $chat = DB::transaction(function () use ($request, $user): ChatModel {
            $character = CharacterModel::create([
                'user_id' => $user->id,
                'name' => $request->string('name')->value(),
                'prompt' => $request->string('prompt')->value(),
            ]);

            if ($request->hasFile('avatar')) {
                $media = MediaUploader::fromSource($request->file('avatar'))
                    ->toDestination('public', 'characters')
                    ->useHashForFilename()
                    ->upload();

                ImageManipulator::createImageVariant($media, 'square');

                $character->attachMedia($media, 'avatar');
            }

            return ChatModel::firstOrCreate([
                'user_id' => $user->id,
                'character_id' => $character->id,
            ]);
        });

        return redirect()->route('chat.show', $chat);
    }

    /**
     * @return Builder<CharacterModel>
     */
    private function buildBrowseQuery(Request $request): Builder
    {
        $query = CharacterModel::query()
            ->regular()
            ->whereNull('deleted_at');

        $q = $request->string('q')->trim()->toString();
        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $query->where(function (Builder $sub) use ($like): void {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('description', 'ilike', $like);
            });
        }

        $category = $request->string('category')->trim()->toString();
        if ($category !== '') {
            $query->whereHas('categories', function (Builder $sub) use ($category): void {
                $sub->where('slug->pl', $category);
            });
        }

        if ($request->boolean('official')) {
            $query->where('is_official', true);
        }

        $sort = $request->string('sort')->toString();
        match ($sort) {
            'new' => $query->orderByDesc('created_at'),
            default => $query
                ->orderByDesc('is_official')
                ->orderByDesc('popularity_24h')
                ->orderByDesc('created_at'),
        };

        return $query;
    }
}
