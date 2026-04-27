@extends('layouts.app', ['title' => $character->name.' — postac.ai'])

@section('content')
    <article class="mx-auto w-full max-w-3xl px-4 py-8 sm:py-12">
        <header class="mb-8 flex flex-col items-center gap-6 sm:flex-row sm:items-start">
            <img
                src="{{ $character->avatarUrl('square') }}"
                alt=""
                class="h-40 w-40 shrink-0 rounded-2xl object-cover shadow-lg sm:h-48 sm:w-48"
            >
            <div class="flex-1 text-center sm:text-left">
                <div class="flex items-center justify-center gap-2 sm:justify-start">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $character->name }}</h1>
                    @if ($character->is_official)
                        <span class="rounded-full bg-primary px-2 py-0.5 text-xs font-semibold uppercase text-primary-content">Oficjalna</span>
                    @endif
                </div>

                @if ($character->description)
                    <p class="mt-3 text-base-content/80">{{ $character->description }}</p>
                @endif

                @if ($character->categories->isNotEmpty() || $character->freeTags->isNotEmpty())
                    <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                        @foreach ($character->categories as $cat)
                            <span class="badge badge-primary badge-outline">{{ $cat->name }}</span>
                        @endforeach
                        @foreach ($character->freeTags as $tag)
                            <span class="badge badge-ghost">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                @if (! $character->is_official && $character->author)
                    <p class="mt-3 text-xs text-base-content/60">@ {{ $character->author->name }}</p>
                @endif

                <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm text-base-content/70 sm:justify-start">
                    @if ($character->popularity_24h > 0)
                        <span>🔥 {{ $character->popularity_24h }} rozmów dziś</span>
                    @endif
                    <span>{{ $character->chats()->count() }} rozmów łącznie</span>
                </div>
            </div>
        </header>

        @if ($character->is_official)
            <div class="mb-6 rounded-lg bg-base-200 px-4 py-3 text-sm text-base-content/70">
                Postać AI inspirowana {{ $character->name }}. To fikcyjna interpretacja, nie wierne odwzorowanie.
            </div>
        @endif

        <form method="POST" action="{{ route('chat.store') }}" class="text-center">
            @csrf
            <input type="hidden" name="character_id" value="{{ $character->id }}">
            <button type="submit" class="btn btn-primary btn-lg">Rozpocznij rozmowę</button>
        </form>
    </article>
@endsection
