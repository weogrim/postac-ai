<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Character\Models\CharacterModel;
use App\Chat\Enums\LimitType;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\Chat\Models\MessageModel;
use App\User\EnsureGhostUser;
use App\User\Models\UserModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('home');
        }

        /** @var UserModel $user */
        $latest = $user->chats()->latest()->first();

        return $latest !== null
            ? redirect()->route('chat.show', $latest)
            : redirect()->route('home');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', Rule::exists(CharacterModel::class, 'id')],
        ]);

        $user = app(EnsureGhostUser::class)->forRequest($request);

        $chat = ChatModel::firstOrCreate([
            'user_id' => $user->id,
            'character_id' => $data['character_id'],
        ]);

        if ($chat->wasRecentlyCreated) {
            $character = CharacterModel::query()->whereKey($data['character_id'])->first();

            if ($character !== null && filled($character->greeting)) {
                MessageModel::create([
                    'chat_id' => $chat->id,
                    'sender_role' => SenderRole::Character,
                    'character_id' => $character->id,
                    'content' => $character->greeting,
                ]);
            }
        }

        return redirect()->route('chat.show', $chat);
    }

    public function show(Request $request, ChatModel $chat): View
    {
        abort_unless($chat->user_id === $request->user()?->id, 404);

        /** @var UserModel $user */
        $user = $request->user();

        $chat->load(['character.media', 'messages']);

        $chats = $user->chats()
            ->with('character.media')
            ->latest()
            ->get();

        $gateLocked = $user->isGuest() && MessageLimitModel::query()
            ->where('user_id', $user->id)
            ->where('limit_type', LimitType::Guest->value)
            ->whereColumn('used', '>=', 'quota')
            ->exists();

        return view('chat.show', [
            'chat' => $chat,
            'chats' => $chats,
            'gateLocked' => $gateLocked,
        ]);
    }
}
