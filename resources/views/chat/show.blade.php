@extends('layouts.app')

@section('footer')<span class="hidden"></span>@endsection

@section('content')
    <div class="flex h-[calc(100dvh-4rem)] w-full">
        <aside class="hidden w-72 shrink-0 flex-col overflow-y-auto border-r border-line bg-panel p-3 lg:flex">
            <div class="mb-3 flex items-center justify-between px-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-ink-mute">Twoje czaty</span>
                <a href="{{ route('character.index') }}" class="btn btn-ghost btn-circle btn-sm" aria-label="Nowy czat">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
            </div>

            <ul class="flex flex-col gap-1">
                @foreach ($chats as $chatItem)
                    <li>
                        <a
                            href="{{ route('chat.show', $chatItem) }}"
                            class="flex items-center gap-3 rounded-xl px-2 py-2 transition-colors {{ $chatItem->id === $chat->id ? 'bg-panel-2 text-ink' : 'text-ink-dim hover:bg-panel-2/60' }}"
                        >
                            <x-character-avatar :character="$chatItem->character" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $chatItem->character->name }}</p>
                                <p class="truncate text-xs text-ink-mute">@ {{ $chatItem->character->author?->name ?? 'nieznany' }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <div class="flex flex-1 min-w-0 flex-col items-center px-3 py-4 sm:px-6 lg:px-8">
            <div class="card-glass w-full max-w-3xl flex flex-col flex-1 min-h-0 p-5 sm:p-6"
                 data-chat data-chat-id="{{ $chat->id }}">

                <header class="flex items-center gap-3 pb-4 border-b border-line">
                    <x-character-avatar :character="$chat->character" size="sm" />
                    <div class="min-w-0">
                        <h1 class="font-semibold text-ink flex items-center gap-1.5 truncate">
                            {{ $chat->character->name }}
                            @if ($chat->character->is_official)
                                <svg class="w-4 h-4 text-cyan shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </h1>
                        <p class="truncate text-xs text-ink-mute">
                            {{ $chat->character->role_label ?? '@'.($chat->character->author?->name ?? 'nieznany') }}
                        </p>
                    </div>
                </header>

                <div id="messages" class="flex-1 min-h-0 overflow-y-auto space-y-3 py-6">
                    @forelse ($chat->messages as $message)
                        @include('chat._message', ['message' => $message])
                    @empty
                        <div data-empty class="py-12 text-center text-sm text-ink-mute">
                            Powiedz cześć — {{ $chat->character->name }} czeka.
                        </div>
                    @endforelse
                </div>

                @include('chat._composer', ['chat' => $chat, 'locked' => $gateLocked ?? false])

                <p class="mt-3 pt-3 border-t border-line text-xs text-ink-mute text-center">
                    <span class="text-orange">⚠</span>
                    To AI — odpowiedzi mogą być niedokładne. Traktuj jako rozrywkę.
                </p>
            </div>
        </div>
    </div>

    @include('chat._gate-modal', ['open' => $gateLocked ?? false])

    <script>
        (() => {
            const root = document.querySelector('[data-chat]');
            if (!root) return;
            const messages = document.getElementById('messages');
            const form = root.querySelector('[data-chat-form]');
            const input = root.querySelector('[data-chat-input]');
            const streamUrl = @json(route('message.stream', $chat));

            if (!form || !input) return;

            const scrollToBottom = () => messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });

            scrollToBottom();

            let eventSource = null;

            form.addEventListener('htmx:after:swap', (e) => {
                const status = e.detail?.ctx?.response?.status ?? 0;
                if (status < 200 || status >= 300) return;
                input.value = '';
                messages.querySelector('[data-empty]')?.remove();
                scrollToBottom();

                const bubble = messages.querySelector('[data-streaming="true"]');
                if (!bubble) return;

                if (eventSource) eventSource.close();
                eventSource = new EventSource(streamUrl);

                eventSource.onmessage = (ev) => {
                    let payload;
                    try { payload = JSON.parse(ev.data); } catch { return; }

                    if (payload.stop) {
                        bubble.removeAttribute('data-streaming');
                        eventSource.close();
                        eventSource = null;
                        return;
                    }
                    if (payload.error) {
                        bubble.textContent = '⚠️ Nie udało się pobrać odpowiedzi. Spróbuj ponownie.';
                        bubble.removeAttribute('data-streaming');
                        eventSource.close();
                        eventSource = null;
                        return;
                    }
                    if (typeof payload.replace === 'string') {
                        bubble.textContent = payload.replace;
                        scrollToBottom();
                        return;
                    }
                    if (typeof payload.delta === 'string') {
                        bubble.textContent += payload.delta;
                        scrollToBottom();
                    }
                };

                eventSource.onerror = () => {
                    bubble.removeAttribute('data-streaming');
                    eventSource?.close();
                    eventSource = null;
                };
            });

            form.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey && e.target === input) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
        })();
    </script>
@endsection
