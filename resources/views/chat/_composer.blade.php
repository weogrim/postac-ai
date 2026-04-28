@props(['chat', 'locked' => false])

<div id="composer" class="pt-4 border-t border-line">
    @if ($locked)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-panel-2 border border-line px-4 py-3">
            <div class="text-sm">
                <p class="font-semibold text-ink">Załóż konto, żeby pisać dalej.</p>
                <p class="text-ink-mute">Twoja rozmowa zostanie zachowana.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm" hx-boost="false">Mam konto</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm rounded-full" hx-boost="false">Zarejestruj się</a>
            </div>
        </div>
    @else
        <form
            data-chat-form
            hx-post="{{ route('message.store', $chat) }}"
            hx-target="#messages"
            hx-swap="beforeend"
            hx-disable="this"
            class="flex items-center gap-3"
        >
            @csrf
            <textarea
                name="content"
                rows="1"
                placeholder="Napisz wiadomość…"
                class="textarea flex-1 resize-none max-h-40 min-h-[2.75rem] py-2.5 px-5 rounded-3xl bg-panel-2 border border-line text-ink focus:outline-none focus:border-violet"
                required
                maxlength="8000"
                data-chat-input
            ></textarea>
            <button type="submit" class="btn btn-primary btn-circle shrink-0" aria-label="Wyślij">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 5l7 7-7 7" />
                </svg>
            </button>
        </form>
    @endif
</div>
