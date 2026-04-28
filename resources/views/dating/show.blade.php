@extends('layouts.app', ['title' => $character->name.' — Randki'])

@section('content')
    @php($p = $character->datingProfile)

    <section class="relative overflow-hidden py-12">
        <div class="bg-blob"></div>

        <div class="container-app relative z-10 mx-auto max-w-3xl">
            <article class="card-glass p-6 sm:p-8">
                <header class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                    <x-character-avatar :character="$character" size="xl" />

                    <div class="flex-1 text-center sm:text-left">
                        <h1 class="text-display-md">{{ $character->name }}, {{ $p->age }}</h1>
                        <p class="mt-1 text-ink-dim">📍 {{ $p->city }}</p>

                        @if (! empty($p->interests))
                            <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                                @foreach ($p->interests as $interest)
                                    <span class="badge badge-outline badge-sm">{{ $interest }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </header>

                <div class="mt-8 rounded-2xl bg-panel-2 border border-line px-4 py-3 text-sm text-ink-dim">
                    <span class="text-orange">⚠</span>
                    Postać AI. To zabawa, nie prawdziwa osoba — luźny flirt, zero NSFW.
                </div>

                @if ($p->bio)
                    <p class="mt-6 whitespace-pre-line text-ink">{{ $p->bio }}</p>
                @endif

                <form method="POST" action="{{ route('chat.store') }}" class="mt-8 text-center">
                    @csrf
                    <input type="hidden" name="character_id" value="{{ $character->id }}">
                    <button type="submit" class="btn-glow btn-glow-warm">Napisz &rarr;</button>
                </form>
            </article>
        </div>
    </section>
@endsection
