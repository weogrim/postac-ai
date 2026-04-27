<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Chat\Enums\SenderRole;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\Chat\Requests\MessageStoreRequest;
use App\Chat\ReserveMessageQuota;
use App\User\EnsureGhostUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MessageController
{
    public function store(MessageStoreRequest $request, ChatModel $chat): Response
    {
        $user = app(EnsureGhostUser::class)->forRequest($request);

        abort_unless($chat->user_id === $user->id, 404);

        $model = app(ReserveMessageQuota::class)->reserve($user);

        [$userMessage, $characterMessage] = DB::transaction(function () use ($chat, $request, $user, $model) {
            $userMessage = MessageModel::create([
                'chat_id' => $chat->id,
                'sender_role' => SenderRole::User,
                'user_id' => $user->id,
                'content' => $request->string('content')->toString(),
                'model' => $model->value,
            ]);

            $character = MessageModel::create([
                'chat_id' => $chat->id,
                'sender_role' => SenderRole::Character,
                'character_id' => $chat->character_id,
                'content' => '',
                'model' => $model->value,
            ]);

            return [$userMessage, $character];
        });

        $userMessage->load('user');
        $characterMessage->load('character.media');

        $html = view('chat._message', ['message' => $userMessage])->render()
            .view('chat._message', ['message' => $characterMessage, 'streaming' => true])->render();

        return response($html, 201)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Character-Message-Id', $characterMessage->id);
    }
}
