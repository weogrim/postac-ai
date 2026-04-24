<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $latest = $user->chats()->latest()->first();

        return $latest !== null
            ? redirect()->route('chat.show', $latest)
            : redirect()->route('home');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', Rule::exists(Character::class, 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();

        $chat = Chat::firstOrCreate([
            'user_id' => $user->id,
            'character_id' => $data['character_id'],
        ]);

        return redirect()->route('chat.show', $chat);
    }

    public function show(Request $request, Chat $chat): View
    {
        abort_unless($chat->user_id === $request->user()?->id, 404);

        /** @var User $user */
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
