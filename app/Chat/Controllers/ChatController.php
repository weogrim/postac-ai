<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Character\Enums\CharacterKind;
use App\Character\Models\CharacterModel;
use App\Chat\Enums\LimitType;
use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageLimitModel;
use App\Chat\Models\MessageModel;
use App\Dating\HasAcceptedDatingTerms;
use App\User\EnsureGhostUser;
use App\User\Models\UserModel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
        $latest = $user->chats()
            ->whereHas('messages', fn (Builder $q) => $q->where('sender_role', SenderRole::User->value))
            ->latest()
            ->first();

        return $latest !== null
            ? redirect()->route('chat.show', $latest)
            : redirect()->route('home');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', Rule::exists(CharacterModel::class, 'id')],
        ]);

        $character = CharacterModel::query()->whereKey($data['character_id'])->firstOrFail();

        if ($character->kind === CharacterKind::Dating) {
            $authUser = $request->user();

            if ($authUser === null || $authUser->isGuest()) {
                return redirect()->route('login')
                    ->with('status', 'Zaloguj się, żeby napisać do tej postaci.');
            }

            if (! app(HasAcceptedDatingTerms::class)->check($authUser)) {
                return redirect()->route('dating.onboarding');
            }
        }

        $user = app(EnsureGhostUser::class)->forRequest($request);

        $chat = ChatModel::firstOrCreate([
            'user_id' => $user->id,
            'character_id' => $character->id,
        ]);

        if ($chat->wasRecentlyCreated && filled($character->greeting)) {
            MessageModel::create([
                'chat_id' => $chat->id,
                'sender_role' => SenderRole::Character,
                'character_id' => $character->id,
                'content' => $character->greeting,
            ]);
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
            ->whereHas('messages', fn (Builder $q) => $q->where('sender_role', SenderRole::User->value))
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
