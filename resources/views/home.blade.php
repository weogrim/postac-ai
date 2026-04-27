@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12">
        <header class="mb-10 text-center">
            <h1 class="text-3xl font-bold tracking-tight sm:text-5xl">Z kim chcesz porozmawiać?</h1>
            <form
                method="GET"
                action="{{ route('character.index') }}"
                class="mx-auto mt-6 flex max-w-xl"
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
                    class="input input-bordered input-lg w-full"
                    autocomplete="off"
                >
            </form>
        </header>

        @if ($popular->isNotEmpty())
            <div class="mb-12">
                <div class="mb-4 flex items-end justify-between">
                    <h2 class="text-xl font-semibold">Teraz popularne</h2>
                    <a href="{{ route('character.index') }}" class="link link-primary text-sm">Zobacz wszystkie →</a>
                </div>
                <div id="popular-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($popular as $character)
                        <x-character-card :character="$character" />
                    @endforeach
                </div>
            </div>
        @endif

        @if ($categories->isNotEmpty())
            <div class="mb-12">
                <h2 class="mb-4 text-xl font-semibold">Kategorie</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($categories as $cat)
                        <a
                            href="{{ route('character.index', ['category' => $cat->slug]) }}"
                            class="btn btn-sm btn-outline"
                        >
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($latest->isNotEmpty())
            <div class="mb-12">
                <h2 class="mb-4 text-xl font-semibold">Nowe i ciekawe</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($latest as $character)
                        <x-character-card :character="$character" />
                    @endforeach
                </div>
            </div>
        @endif

        @auth
            <div class="mt-12 rounded-2xl border border-base-300 bg-base-100 p-8 text-center">
                <h3 class="text-lg font-semibold">Masz pomysł na własną postać?</h3>
                <p class="mt-2 text-sm text-base-content/70">Stwórz ją i podziel się ze społecznością.</p>
                <a href="{{ route('character.create') }}" class="btn btn-primary mt-4">➕ Stwórz swoją postać</a>
            </div>
        @endauth

        @if ($popular->isEmpty() && $latest->isEmpty())
            <div class="rounded-2xl border border-base-300 bg-base-100 p-10 text-center">
                <p class="text-lg font-medium">Brak postaci</p>
                <p class="mt-2 text-sm text-base-content/70">
                    @auth
                        Stwórz swoją pierwszą postać i zacznij rozmawiać.
                    @else
                        Zaloguj się, żeby stworzyć pierwszą postać.
                    @endauth
                </p>
                <div class="mt-6">
                    @auth
                        <a href="{{ route('character.create') }}" class="btn btn-primary">Dodaj postać</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Zaloguj</a>
                    @endauth
                </div>
            </div>
        @endif
    </section>
@endsection
