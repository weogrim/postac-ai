@extends('layouts.app', ['title' => 'Postacie — postac.ai'])

@section('content')
    <section class="mx-auto w-full max-w-6xl px-4 py-8 sm:py-12">
        <header class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Wszystkie postacie</h1>
            <p class="mt-2 text-sm text-base-content/70">Z kim chcesz porozmawiać?</p>
        </header>

        <form
            method="GET"
            action="{{ route('character.index') }}"
            class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center"
            hx-get="{{ route('character.search') }}"
            hx-trigger="input changed delay:300ms from:input[name=q], change from:select[name=category], change from:select[name=sort], change from:input[name=official]"
            hx-target="#characters-grid"
            hx-swap="innerHTML"
            hx-push-url="true"
        >
            <input
                type="search"
                name="q"
                value="{{ $q }}"
                placeholder="Szukaj postaci…"
                class="input input-bordered w-full sm:flex-1"
                autocomplete="off"
            >
            <select name="category" class="select select-bordered sm:w-56">
                <option value="">Wszystkie kategorie</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->slug }}" @selected($category === $cat->slug)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="sort" class="select select-bordered sm:w-44">
                <option value="popular" @selected($sort === 'popular')>Popularne</option>
                <option value="new" @selected($sort === 'new')>Nowe</option>
            </select>
            <label class="label cursor-pointer gap-2">
                <input
                    type="checkbox"
                    name="official"
                    value="1"
                    class="checkbox checkbox-primary"
                    @checked($official)
                >
                <span class="label-text">Tylko oficjalne</span>
            </label>
        </form>

        <div id="characters-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @include('characters._grid', ['characters' => $characters])
        </div>

        <div class="mt-8">
            {{ $characters->links() }}
        </div>
    </section>
@endsection
