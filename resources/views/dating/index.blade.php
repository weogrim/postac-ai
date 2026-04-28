@extends('layouts.app', ['title' => 'Randki — postac.ai'])

@section('content')
    <section class="relative overflow-hidden py-12 sm:py-20">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10">
            <header class="mb-12 text-center">
                <span class="eyebrow">Randki</span>
                <h1 class="text-display-xl mt-6">
                    Pogadaj z <span class="text-gradient-warm">kimś&nbsp;bliskim</span>
                </h1>
                <p class="mt-4 max-w-xl mx-auto text-ink-dim">
                    Postacie AI do luźnego flirtu i rozmowy. To zabawa — pamiętaj, że to nie prawdziwi ludzie.
                </p>
            </header>

            @if ($profiles->isEmpty())
                <div class="card-glass p-10 text-center max-w-md mx-auto">
                    <p class="font-display text-lg">Jeszcze nikogo tu nie ma 💌</p>
                    <p class="mt-2 text-sm text-ink-dim">Wróć wkrótce.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($profiles as $character)
                        @php($p = $character->datingProfile)
                        <a
                            href="{{ route('dating.show', $character) }}"
                            class="card-glass p-5 block group relative"
                            @if ($p->accent_color) style="--accent: {{ $p->accent_color }}; box-shadow: 0 0 0 1px {{ $p->accent_color }}25" @endif
                        >
                            <x-character-avatar :character="$character" size="lg" />

                            <h3 class="mt-4 font-display text-lg font-semibold text-ink truncate">
                                {{ $character->name }}, {{ $p->age }}
                            </h3>
                            <p class="mt-1 text-xs uppercase tracking-wider font-semibold text-ink-dim">📍 {{ $p->city }}</p>

                            @if ($p->bio)
                                <p class="mt-3 text-sm text-ink-dim line-clamp-3">{{ $p->bio }}</p>
                            @endif

                            <div class="mt-5 pt-4 border-t border-line text-xs text-ink-mute flex items-center justify-between">
                                <span class="uppercase tracking-wide font-semibold">Randki</span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose"></span>
                                    Online
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $profiles->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
