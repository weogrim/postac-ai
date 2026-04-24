@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12">
        <header class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Postacie</h1>
                <p class="mt-1 text-sm text-base-content/70">Wybierz postać i zacznij rozmowę.</p>
            </div>

            @auth
                <a href="{{ route('character.create') }}" class="btn btn-primary btn-sm sm:btn-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Dodaj postać</span>
                </a>
            @endauth
        </header>

        @if ($characters->isEmpty())
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
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-6">
                @include('partials._character-grid-page', ['characters' => $characters])
            </div>
        @endif
    </section>
@endsection
