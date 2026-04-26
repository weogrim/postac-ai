<?php

declare(strict_types=1);

namespace App\Chat\Controllers;

use App\Chat\Enums\SenderRole;
use App\Chat\MessageStreamer;
use App\Chat\Models\ChatModel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MessageStreamController
{
    public function __invoke(Request $request, ChatModel $chat, MessageStreamer $streamer): StreamedResponse
    {
        abort_unless($chat->user_id === $request->user()?->id, 404);

        $characterMessage = $chat->messages()
            ->where('sender_role', SenderRole::Character->value)
            ->where('content', '')
            ->latest()
            ->first();

        abort_if($characterMessage === null, 404, 'Brak oczekującej wiadomości postaci.');

        return response()->stream(function () use ($streamer, $chat, $characterMessage): void {
            $full = '';
            $completionTokens = 0;

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
