@extends('layouts.app', ['title' => $character->name.' — Randki'])

@section('content')
    @php($p = $character->datingProfile)
    <article class="mx-auto w-full max-w-3xl px-4 py-8 sm:py-12">
        <header class="mb-8 flex flex-col items-center gap-6 sm:flex-row sm:items-start">
            <img
                src="{{ $character->avatarUrl('square') }}"
                alt=""
                class="h-40 w-40 shrink-0 rounded-2xl object-cover shadow-lg sm:h-48 sm:w-48"
                @if ($p->accent_color) style="box-shadow: 0 10px 30px -5px {{ $p->accent_color }}40" @endif
            >
            <div class="flex-1 text-center sm:text-left">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $character->name }}, {{ $p->age }}</h1>
                <p class="mt-1 text-base-content/70">📍 {{ $p->city }}</p>

                @if (! empty($p->interests))
                    <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                        @foreach ($p->interests as $interest)
                            <span class="badge badge-primary badge-outline">{{ $interest }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </header>

        <div class="mb-6 rounded-lg bg-base-200 px-4 py-3 text-sm text-base-content/70">
            Postać AI. To zabawa, nie prawdziwa osoba — luźny flirt, zero NSFW.
        </div>

        <p class="mb-8 whitespace-pre-line text-base-content/90">{{ $p->bio }}</p>

        <form method="POST" action="{{ route('chat.store') }}" class="text-center">
            @csrf
            <input type="hidden" name="character_id" value="{{ $character->id }}">
            <button type="submit" class="btn btn-primary btn-lg">Napisz</button>
        </form>
    </article>
@endsection
