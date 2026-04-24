<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReserveMessageQuota;
use App\AI\ModelType;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Messaging\SenderRole;
use App\Models\Chat;
use App\Models\Message;
use App\Settings\ChatSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, Chat $chat, ReserveMessageQuota $reserve): Response
    {
        abort_unless($chat->user_id === $request->user()?->id, 404);

        $model = $reserve($request->user());

        [$userMessage, $characterMessage] = DB::transaction(function () use ($chat, $request, $model) {
            $user = Message::create([
                'chat_id' => $chat->id,
                'sender_role' => SenderRole::User,
                'user_id' => $request->user()->id,
                'content' => $request->string('content')->toString(),
                'model' => $model->value,
            ]);

            $character = Message::create([
                'chat_id' => $chat->id,
                'sender_role' => SenderRole::Character,
                'character_id' => $chat->character_id,
                'content' => '',
                'model' => $model->value,
            ]);

            return [$user, $character];
        });

        $userMessage->load('user');
        $characterMessage->load('character.media');

        $html = view('chat._message', ['message' => $userMessage])->render()
            .view('chat._message', ['message' => $characterMessage, 'streaming' => true])->render();

        return response($html, 201)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Character-Message-Id', $characterMessage->id);
    }

    public function stream(Request $request, Chat $chat, ChatSettings $settings): StreamedResponse
    {
        abort_unless($chat->user_id === $request->user()?->id, 404);

        $characterMessage = $chat->messages()
            ->where('sender_role', SenderRole::Character->value)
            ->where('content', '')
            ->latest()
            ->first();

        abort_if($characterMessage === null, 404, 'Brak oczekującej wiadomości postaci.');

        $chat->load('character');

        $conversation = $chat->messages()
            ->where('id', '!=', $characterMessage->id)
            ->where(fn ($q) => $q
                ->where('sender_role', SenderRole::User->value)
                ->orWhere(fn ($q) => $q
                    ->where('sender_role', SenderRole::Character->value)
                    ->where('content', '!=', '')))
            ->latest()
            ->take($settings->historyLength + 1)
            ->get()
            ->reverse()
            ->values();

        $lastUserMessage = $conversation->last();

        abort_if($lastUserMessage === null, 404, 'Brak wiadomości użytkownika do odpowiedzi.');

        $history = $conversation
            ->slice(0, -1)
            ->map(fn (Message $m) => $m->sender_role === SenderRole::User
                ? new UserMessage($m->content)
                : new AssistantMessage($m->content))
            ->all();

        $latestUserContent = trim(
            $settings->beforeUserMessage.' '
            .$lastUserMessage->content
            .' '.$settings->afterUserMessage
        );

        $model = ModelType::from($characterMessage->model ?? ModelType::Gpt4oMini->value);

        $agent = new AnonymousAgent(
            instructions: $chat->character->prompt,
            messages: $history,
            tools: [],
        );

        $response = $agent->stream(
            prompt: $latestUserContent,
            provider: $model->provider(),
            model: $model->value,
        );

        return response()->stream(function () use ($response, $characterMessage): void {
            $full = '';
            $completionTokens = 0;

            try {
                foreach ($response as $event) {
                    if ($event instanceof TextDelta) {
                        $full .= $event->delta;
                        echo 'data: '.json_encode(['delta' => $event->delta])."\n\n";
                        if (ob_get_level() > 0) {
                            @ob_flush();
                        }
                        flush();
                    }

                    if ($event instanceof StreamEnd) {
                        $completionTokens = $event->usage->completionTokens;
                    }
                }
            } catch (Throwable $e) {
                report($e);
                echo 'data: '.json_encode(['error' => 'stream_failed'])."\n\n";
                flush();
            }

            $characterMessage->update([
                'content' => $full,
                'tokens_usage' => $completionTokens,
            ]);

            echo "data: {\"stop\":true}\n\n";
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
