@extends('layouts.app')

@section('content')
    <div class="drawer lg:drawer-open">
        <input id="chat-drawer" type="checkbox" class="drawer-toggle">

        <div class="drawer-content flex flex-col">
            <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col px-4 py-4 sm:px-6" data-chat data-chat-id="{{ $chat->id }}">
                <header class="flex items-center gap-3 border-b border-base-300 pb-4">
                    <label for="chat-drawer" class="btn btn-ghost btn-circle btn-sm lg:hidden" aria-label="Pokaż listę czatów">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>

                    <div class="avatar">
                        <div class="h-10 w-10 rounded-full">
                            <img src="{{ $chat->character->avatarUrl('square') }}" alt="">
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h1 class="truncate text-base font-semibold">{{ $chat->character->name }}</h1>
                        <p class="truncate text-xs text-base-content/60">@ {{ $chat->character->author?->name ?? 'nieznany' }}</p>
                    </div>
                </header>

                <div id="messages" class="flex-1 space-y-4 overflow-y-auto py-6">
                    @forelse ($chat->messages as $message)
                        @include('chat._message', ['message' => $message])
                    @empty
                        <div class="flex h-full items-center justify-center py-20 text-center text-sm text-base-content/60">
                            Napisz pierwszą wiadomość, żeby zacząć rozmowę.
                        </div>
                    @endforelse
                </div>

                <form
                    data-chat-form
                    hx-post="{{ route('message.store', $chat) }}"
                    hx-target="#messages"
                    hx-swap="beforeend"
                    hx-disable="this"
                    class="sticky bottom-0 flex items-end gap-2 border-t border-base-300 bg-base-200 py-4"
                >
                    @csrf
                    <textarea
                        name="content"
                        rows="1"
                        placeholder="Napisz wiadomość..."
                        class="textarea textarea-bordered max-h-40 w-full resize-none"
                        required
                        maxlength="8000"
                        data-chat-input
                    ></textarea>
                    <button type="submit" class="btn btn-primary btn-square" aria-label="Wyślij">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 5l7 7-7 7" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="drawer-side z-30">
            <label for="chat-drawer" class="drawer-overlay" aria-label="Zamknij"></label>

            <aside class="flex h-full w-72 flex-col bg-base-100 p-3">
                <div class="mb-3 flex items-center justify-between px-1">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-base-content/60">Twoje czaty</h2>
                    <a href="{{ route('home') }}" class="btn btn-ghost btn-circle btn-sm" aria-label="Nowy czat">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </a>
                </div>

                <ul class="menu menu-sm w-full gap-1">
                    @foreach ($chats as $chatItem)
                        <li>
                            <a
                                href="{{ route('chat.show', $chatItem) }}"
                                class="flex items-center gap-3 {{ $chatItem->id === $chat->id ? 'menu-active' : '' }}"
                            >
                                <div class="avatar">
                                    <div class="h-10 w-10 rounded-full">
                                        <img src="{{ $chatItem->character->avatarUrl('square') }}" alt="">
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ $chatItem->character->name }}</p>
                                    <p class="truncate text-xs text-base-content/60">@ {{ $chatItem->character->author?->name ?? 'nieznany' }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-chat]');
            if (!root) return;
            const chatId = root.dataset.chatId;
            const messages = document.getElementById('messages');
            const form = root.querySelector('[data-chat-form]');
            const input = root.querySelector('[data-chat-input]');
            const streamUrl = @json(route('message.stream', $chat));

            const scrollToBottom = () => messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });

            scrollToBottom();

            let eventSource = null;

            form.addEventListener('htmx:after:swap', (e) => {
                const status = e.detail?.ctx?.response?.status ?? 0;
                if (status < 200 || status >= 300) return;
                input.value = '';
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
