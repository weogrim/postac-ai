<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Character\StoreCharacterRequest;
use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Plank\Mediable\Facades\ImageManipulator;
use Plank\Mediable\Facades\MediaUploader;

class CharacterController extends Controller
{
    public function create(): View
    {
        return view('characters.create');
    }

    public function store(StoreCharacterRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $chat = DB::transaction(function () use ($request, $user): Chat {
            $character = Character::create([
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

            return Chat::firstOrCreate([
                'user_id' => $user->id,
                'character_id' => $character->id,
            ]);
        });

        return redirect()->route('chat.show', $chat);
    }
}
