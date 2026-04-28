@extends('layouts.app')

@section('content')
    {{-- ========================= HERO ========================= --}}
    <section class="relative overflow-hidden py-16 lg:py-24">
        <div class="bg-blob"></div>

        {{-- Lewa kolumna szersza niż prawa: chat preview ma max-w-md (~448px), hero
             zyskuje miejsce żeby długie imiona ("Maria Skłodowska-Curie") mieściły się
             w 2 liniach bez layout shiftu. --}}
        <div class="container-app relative z-10 grid gap-12 lg:grid-cols-[1.5fr_1fr] xl:grid-cols-[1.7fr_1fr] lg:items-center">
            <div>
                <span class="eyebrow">Polska platforma AI · {{ $totalCharacters > 0 ? $totalCharacters : '30+' }} postaci po polsku</span>

                <h1 class="text-display-xl mt-6">
                    <span class="block whitespace-nowrap">Porozmawiaj z</span>
                    {{-- Mniejszy font (text-display-lg = max 56px) na imieniu, żeby
                         najdłuższe nominativy ("Maria Skłodowska-Curie", "Adam Mickiewicz")
                         zmieściły się w max 2 liniach przy realnej szerokości kolumny.
                         min-h-[2.1em] rezerwuje miejsce na 2 linie żeby nie skakało
                         przy zmianie krótkiej nazwy na długą. --}}
                    <span class="text-display-lg block min-h-[2.1em]">
                        <span
                            x-data="{ names: @js($rotatingNames ?: ['Marią Curie', 'Józefem Piłsudskim', 'Mikołajem Kopernikiem']), i: 0 }"
                            x-init="setInterval(() => i = (i + 1) % names.length, 2400)"
                            class="text-gradient-brand--animated"
                            x-text="names[i]"
                        >{{ $rotatingNames[0] ?? 'Marią Curie' }}</span>.
                    </span>
                </h1>

                <h2 class="text-display-md text-ink-dim mt-6">Po polsku. Bez udawania.</h2>

                <p class="mt-6 max-w-prose text-ink-dim">
                    Pierwsza polska platforma rozmów z postaciami AI. Historia, fikcja,
                    pomoc w nauce i osobna sekcja randek — wszystko po polsku, z polską
                    kulturą i bez NSFW. W 10 sekund od wejścia już rozmawiasz.
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    @registered
                        <a href="{{ route('chat.index') }}" class="btn-glow">Twoje czaty &rarr;</a>
                        <a href="{{ route('character.index') }}" class="btn btn-ghost">Zobacz postacie</a>
                    @else
                        <a href="{{ route('register') }}" class="btn-glow">Zarejestruj się &rarr;</a>
                        <a href="{{ route('character.index') }}" class="btn btn-ghost">Zobacz postacie</a>
                    @endregistered
                </div>

                <div class="mt-16 grid grid-cols-3 gap-8 max-w-xl">
                    <div>
                        <div class="text-display-md">{{ $totalCharacters > 0 ? $totalCharacters : '30+' }}</div>
                        <div class="mt-3 flex items-center gap-1.5 whitespace-nowrap text-[10px] uppercase tracking-wider font-semibold text-ink-dim">
                            <span class="text-violet leading-none">●</span>
                            Postaci na start
                        </div>
                    </div>
                    <div>
                        <div class="text-display-md">13+</div>
                        <div class="mt-3 flex items-center gap-1.5 whitespace-nowrap text-[10px] uppercase tracking-wider font-semibold text-ink-dim">
                            <span class="text-violet leading-none">●</span>
                            Bezpieczna platforma
                        </div>
                    </div>
                    <div>
                        <div class="text-display-md">0 zł</div>
                        <div class="mt-3 flex items-center gap-1.5 whitespace-nowrap text-[10px] uppercase tracking-wider font-semibold text-ink-dim">
                            <span class="text-violet leading-none">●</span>
                            Za start
                        </div>
                    </div>
                </div>
            </div>

            <x-home.chat-preview class="lg:justify-self-end" />
        </div>
    </section>

    {{-- ========================= MARQUEE ========================= --}}
    @if (! empty($marqueeNames))
        <section class="border-t border-line py-12">
            <p class="text-center mb-6">
                <span class="eyebrow">Postacie, które już czekają</span>
            </p>
            <div class="marquee">
                <div class="marquee-track">
                    {{-- 4× duplikacja zapewnia, że track jest szerszy niż 2× viewport
                         na wszystkich rozmiarach ekranu (animacja translateX(-50%)
                         wymaga conajmniej 2× viewport żeby nie pokazywała pustki). --}}
                    @foreach (array_merge($marqueeNames, $marqueeNames, $marqueeNames, $marqueeNames) as $name)
                        <span class="text-ink-dim text-lg whitespace-nowrap">
                            <span class="text-magenta">●</span> {{ $name }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========================= BROWSE ========================= --}}
    <section class="container-app py-section">
        <header class="mb-10">
            <h2 class="text-display-lg">Z kim chcesz porozmawiać?</h2>
            <form
                method="GET"
                action="{{ route('character.index') }}"
                class="mt-6 flex max-w-xl"
                hx-get="{{ route('character.search') }}"
                hx-trigger="input changed delay:300ms"
                hx-target="#popular-grid"
                hx-swap="innerHTML"
                hx-push-url="false"
            >
                <input
                    type="search"
                    name="q"
                    placeholder="🔍 szukaj postaci…"
                    class="input input-bordered input-lg w-full bg-panel-2 border-line rounded-full"
                    autocomplete="off"
                >
            </form>
        </header>

        @if ($popular->isNotEmpty())
            <div class="mb-12">
                <div class="mb-6 flex items-end justify-between">
                    <h3 class="font-display text-xl font-semibold">Teraz popularne</h3>
                    <a href="{{ route('character.index') }}" class="text-magenta text-sm hover:underline">Zobacz wszystkie →</a>
                </div>
                <div id="popular-grid" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($popular as $character)
                        <x-character-card :character="$character" />
                    @endforeach
                </div>
            </div>
        @endif

        @if ($categories->isNotEmpty())
            <div class="mb-12">
                <h3 class="mb-4 font-display text-xl font-semibold">Kategorie</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach ($categories as $cat)
                        <a
                            href="{{ route('character.index', ['category' => $cat->slug]) }}"
                            class="btn btn-sm rounded-full bg-panel-2 border-line text-ink-dim hover:text-ink"
                        >
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($latest->isNotEmpty())
            <div class="mb-12">
                <h3 class="mb-6 font-display text-xl font-semibold">Nowe i ciekawe</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($latest as $character)
                        <x-character-card :character="$character" />
                    @endforeach
                </div>
            </div>
        @endif

        @registered
            <div class="card-glass mt-16 p-8 text-center">
                <h3 class="font-display text-xl font-semibold">Masz pomysł na własną postać?</h3>
                <p class="mt-2 text-sm text-ink-dim">Stwórz ją i podziel się ze społecznością.</p>
                <a href="{{ route('character.create') }}" class="btn-glow mt-6">Stwórz swoją postać &rarr;</a>
            </div>
        @endregistered

        @if ($popular->isEmpty() && $latest->isEmpty())
            <div class="card-glass p-10 text-center">
                <p class="font-display text-lg font-medium">Brak postaci</p>
                <p class="mt-2 text-sm text-ink-dim">
                    @registered
                        Stwórz swoją pierwszą postać i zacznij rozmawiać.
                    @else
                        Zaloguj się, żeby stworzyć pierwszą postać.
                    @endregistered
                </p>
                <div class="mt-6">
                    @registered
                        <a href="{{ route('character.create') }}" class="btn-glow">Dodaj postać</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-glow">Zaloguj</a>
                    @endregistered
                </div>
            </div>
        @endif
    </section>
@endsection
