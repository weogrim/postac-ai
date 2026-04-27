@extends('layouts.app', ['title' => 'Randki — postac.ai'])

@section('content')
    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:py-12">
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">💕 Randki</h1>
            <p class="mt-3 text-base-content/70">Postacie AI do luźnego flirtu i rozmowy. To zabawa — pamiętaj, że to nie prawdziwi ludzie.</p>
        </header>

        @if ($profiles->isEmpty())
            <div class="rounded-xl bg-base-200 p-8 text-center text-base-content/70">
                Jeszcze nikogo tu nie ma. Wróć wkrótce 💌
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                @foreach ($profiles as $character)
                    @php($p = $character->datingProfile)
                    <a
                        href="{{ route('dating.show', $character) }}"
                        class="group relative block aspect-[3/4] overflow-hidden rounded-2xl bg-base-100 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-2 hover:ring-primary/60"
                        @if ($p->accent_color) style="--accent: {{ $p->accent_color }}" @endif
                    >
                        <img src="{{ $character->avatarUrl('square') }}" alt="" loading="lazy" class="pointer-events-none h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]">
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 p-4 text-white">
                            <h3 class="truncate text-lg font-semibold drop-shadow-sm">{{ $character->name }}, {{ $p->age }}</h3>
                            <p class="truncate text-xs text-white/80">{{ $p->city }}</p>
                            <p class="mt-1 line-clamp-2 text-[11px] text-white/70">{{ $p->bio }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $profiles->links() }}
            </div>
        @endif
    </div>
@endsection
