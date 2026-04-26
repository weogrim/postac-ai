<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Character\Models\CharacterModel;
use App\Chat\Models\ChatModel;
use App\User\Models\UserModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController
{
    public function index(Request $request): RedirectResponse
    {
        /** @var UserModel $user */
        $user = $request->user();

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

        /** @var UserModel $user */
        $user = $request->user();

        $chat = ChatModel::firstOrCreate([
            'user_id' => $user->id,
            'character_id' => $data['character_id'],
        ]);

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

        return view('chat.show', [
            'chat' => $chat,
            'chats' => $chats,
        ]);
    }
}
