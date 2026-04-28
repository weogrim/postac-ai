@extends('layouts.app', ['title' => 'Postacie — postac.ai'])

@section('content')
    @php
        $emojis = [
            'Historia' => '🏛️',
            'Fikcja' => '💬',
            'Nauka' => '🧪',
            'Humor' => '😂',
            'Randki' => '💕',
            'Pomocnik' => '🎓',
        ];
    @endphp

    {{-- ========================= HERO ========================= --}}
    <section class="relative overflow-hidden py-16 lg:py-24">
        <div class="bg-blob"></div>
        <div class="container-app relative z-10 text-center">
            <span class="eyebrow">Katalog</span>
            <h1 class="text-display-xl mt-6">
                Każdy znajdzie <span class="text-gradient-brand">kogoś</span><br>swojego
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-ink-dim">
                Ponad {{ $totalCharacters }} postaci na start — od polskich legend historii,
                przez bohaterów literatury, po pomocników, którzy pomogą Ci nauczyć się
                matematyki albo języka. A w sekcji Randki — zupełnie inny świat.
            </p>
        </div>
    </section>

    {{-- ========================= FILTERS ========================= --}}
    <section class="container-app pb-12">
        <form
            method="GET"
            action="{{ route('character.index') }}"
            hx-get="{{ route('character.search') }}"
            hx-trigger="input changed delay:300ms from:input[name=q], change"
            hx-target="#characters-grid"
            hx-swap="innerHTML"
            hx-push-url="true"
            x-data="{ selectedCat: @js($category) }"
            class="flex flex-col items-center gap-6"
        >
            <input type="hidden" name="category" :value="selectedCat" x-ref="catInput">

            <div class="flex flex-wrap gap-2 justify-center">
                <button type="button"
                        @click="selectedCat = ''; $refs.catInput.dispatchEvent(new Event('change', { bubbles: true }))"
                        :class="selectedCat === '' ? 'btn-primary' : 'bg-panel-2 border-line text-ink-dim'"
                        class="btn btn-sm rounded-full">
                    Wszystkie
                </button>
                @foreach ($categories as $cat)
                    <button type="button"
                            @click="selectedCat = @js($cat->slug); $refs.catInput.dispatchEvent(new Event('change', { bubbles: true }))"
                            :class="selectedCat === @js($cat->slug) ? 'btn-primary' : 'bg-panel-2 border-line text-ink-dim'"
                            class="btn btn-sm rounded-full">
                        @if (! empty($emojis[$cat->name]))
                            <span class="mr-1">{{ $emojis[$cat->name] }}</span>
                        @endif
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>

            <div class="w-full max-w-2xl flex flex-col gap-3 sm:flex-row sm:items-center">
                <input
                    type="search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="🔍 Szukaj postaci…"
                    class="input input-bordered flex-1 rounded-full bg-panel-2 border-line"
                    autocomplete="off"
                >
                <select name="sort" class="select select-bordered rounded-full bg-panel-2 border-line sm:w-44">
                    <option value="popular" @selected($sort === 'popular')>Popularne</option>
                    <option value="new" @selected($sort === 'new')>Nowe</option>
                </select>
                <label class="label cursor-pointer gap-2 text-sm">
                    <input type="checkbox" name="official" value="1" class="checkbox checkbox-primary checkbox-sm" @checked($official)>
                    <span class="text-ink-dim">Tylko oficjalne</span>
                </label>
            </div>
        </form>
    </section>

    {{-- ========================= GRID ========================= --}}
    <section class="container-app pb-section">
        <div id="characters-grid" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @include('characters._grid', ['characters' => $characters])
        </div>

        <div class="mt-10">
            {{ $characters->links() }}
        </div>
    </section>
@endsection
