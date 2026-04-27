<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Chat\Enums\SenderRole;
use App\Chat\Exceptions\OutOfMessagesException;
use App\Chat\Models\ChatModel;
use App\Chat\Models\MessageModel;
use App\Chat\Requests\MessageStoreRequest;
use App\Chat\ReserveMessageQuota;
use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\Exceptions\ContentBlockedException;
use App\Moderation\HelplineMessage;
use App\Moderation\Models\SafetyEventModel;
use App\User\EnsureGhostUser;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class MessageController
{
    public function store(MessageStoreRequest $request, ChatModel $chat): Response
    {
        $user = app(EnsureGhostUser::class)->forRequest($request);

        abort_unless($chat->user_id === $user->id, 404);

        $content = $request->string('content')->toString();
        $shKey = 'selfharm:'.$user->id;
        $shLimit = (int) config('moderation.self_harm.rate_limit', 3);
        $shWindow = (int) config('moderation.self_harm.window_seconds', 300);

        if (RateLimiter::tooManyAttempts($shKey, $shLimit)) {
            throw new OutOfMessagesException(
                'Daj sobie chwilę odpocząć — jesteś dla nas ważny. Zadzwoń jeśli potrzebujesz wsparcia: 116 111.'
            );
        }

        $moderation = app(ModerationProvider::class)->check($content);

        if ($moderation->isSelfHarm()) {
            SafetyEventModel::create([
                'user_id' => $user->id,
                'category' => 'self-harm',
            ]);
            RateLimiter::hit($shKey, $shWindow);

            $model = app(ReserveMessageQuota::class)->reserve($user);
            $helpline = app(HelplineMessage::class)->polish();

            [$userMessage, $characterMessage] = DB::transaction(function () use ($chat, $content, $user, $model, $helpline) {
                $userMsg = MessageModel::create([
                    'chat_id' => $chat->id,
                    'sender_role' => SenderRole::User,
                    'user_id' => $user->id,
                    'content' => $content,
                    'model' => $model->value,
                ]);

                $charMsg = MessageModel::create([
                    'chat_id' => $chat->id,
                    'sender_role' => SenderRole::Character,
                    'character_id' => $chat->character_id,
                    'content' => $helpline,
                    'model' => $model->value,
                ]);

                return [$userMsg, $charMsg];
            });

            $userMessage->load('user');
            $characterMessage->load('character.media');

            $html = view('chat._message', ['message' => $userMessage])->render()
                .view('chat._message', ['message' => $characterMessage])->render();

            return response($html, 201)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        if ($moderation->flagged) {
            throw new ContentBlockedException(
                categories: $moderation->categories,
                direction: 'input',
            );
        }

        $model = app(ReserveMessageQuota::class)->reserve($user);

        [$userMessage, $characterMessage] = DB::transaction(function () use ($chat, $content, $user, $model) {
            $userMessage = MessageModel::create([
                'chat_id' => $chat->id,
                'sender_role' => SenderRole::User,
                'user_id' => $user->id,
                'content' => $content,
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
