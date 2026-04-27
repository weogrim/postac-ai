@props(['chat', 'locked' => false])

<div id="composer" class="sticky bottom-0 border-t border-base-300 bg-base-200 py-4">
    @if ($locked)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-base-100 px-4 py-3 shadow-sm">
            <div class="text-sm">
                <p class="font-semibold">Załóż konto, żeby pisać dalej.</p>
                <p class="text-base-content/60">Twoja rozmowa zostanie zachowana.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm" hx-boost="false">Mam konto</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm" hx-boost="false">Zarejestruj się</a>
            </div>
        </div>
    @else
        <form
            data-chat-form
            hx-post="{{ route('message.store', $chat) }}"
            hx-target="#messages"
            hx-swap="beforeend"
            hx-disable="this"
            class="flex items-end gap-2"
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
    @endif
</div>
