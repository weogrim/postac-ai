@extends('layouts.app', ['title' => $document->title.' — postac.ai'])

@section('content')
    <article class="mx-auto w-full max-w-3xl px-4 py-12 sm:py-16">
        <header class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $document->title }}</h1>
            <p class="mt-2 text-sm text-base-content/60">
                Wersja {{ $document->version }}
                @if ($document->published_at)
                    · opublikowano {{ $document->published_at->translatedFormat('j F Y') }}
                @endif
            </p>
        </header>

        <div class="prose max-w-none">
            {!! $rendered !!}
        </div>
    </article>
@endsection
