<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Chat\Enums\SenderRole;
use App\Chat\MessageStreamer;
use App\Chat\Models\ChatModel;
use App\Moderation\Contracts\ModerationProvider;
use App\Moderation\HelplineMessage;
use App\Moderation\Models\SafetyEventModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MessageStreamController
{
    public function __invoke(Request $request, ChatModel $chat): StreamedResponse
    {
        abort_unless($chat->user_id === $request->user()?->id, 404);

        $userId = (int) $request->user()->id;

        $streamer = app(MessageStreamer::class);
        $moderation = app(ModerationProvider::class);
        $helpline = app(HelplineMessage::class);

        $characterMessage = $chat->messages()
            ->where('sender_role', SenderRole::Character->value)
            ->where('content', '')
            ->latest()
            ->first();

        abort_if($characterMessage === null, 404, 'Brak oczekującej wiadomości postaci.');

        return response()->stream(function () use ($streamer, $moderation, $helpline, $chat, $characterMessage, $userId): void {
            $full = '';
            $completionTokens = 0;
            $streamFailed = false;

            try {
                foreach ($streamer->stream($chat, $characterMessage) as $event) {
                    if (isset($event['delta'])) {
                        $full .= $event['delta'];
                        echo 'data: '.json_encode(['delta' => $event['delta']])."\n\n";
                        if (ob_get_level() > 0) {
                            @ob_flush();
                        }
                        flush();
                    }

                    if (isset($event['tokens'])) {
                        $completionTokens = $event['tokens'];
                    }
                }
            } catch (Throwable $e) {
                report($e);
                $streamFailed = true;
                echo 'data: '.json_encode(['error' => 'stream_failed'])."\n\n";
                flush();
            }

            if (! $streamFailed && $full !== '') {
                $result = $moderation->check($full);

                if ($result->isSelfHarm()) {
                    SafetyEventModel::create([
                        'user_id' => $userId,
                        'category' => 'self-harm',
                    ]);
                    RateLimiter::hit(
                        'selfharm:'.$userId,
                        (int) config('moderation.self_harm.window_seconds', 300),
                    );
                    $full = $helpline->polish();
                    echo 'data: '.json_encode(['replace' => $full])."\n\n";
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                } elseif ($result->flagged) {
                    $full = $helpline->fallback();
                    echo 'data: '.json_encode(['replace' => $full])."\n\n";
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                }
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
