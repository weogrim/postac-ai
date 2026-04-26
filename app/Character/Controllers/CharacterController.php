<?php

declare(strict_types=1);

namespace App\Character\Controllers;

use App\Character\Models\CharacterModel;
use App\Character\Requests\CharacterStoreRequest;
use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Plank\Mediable\Facades\ImageManipulator;
use Plank\Mediable\Facades\MediaUploader;

class CharacterController
{
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
}
