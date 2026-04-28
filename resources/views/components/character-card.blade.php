@props(['character'])

@php
    $category = $character->relationLoaded('categories')
        ? $character->categories->first()
        : ($character->relationLoaded('tags') ? $character->tags->firstWhere('type', 'category') : null);
@endphp

<a
    href="{{ route('character.show', $character) }}"
    class="card-glass block p-5 group relative"
>
    @if ($character->is_official)
        <span class="absolute right-3 top-3 inline-flex items-center gap-1 text-xs text-cyan">
            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
        </span>
    @endif

    <x-character-avatar :character="$character" size="lg" :status="true" />

    <h3 class="mt-4 font-display text-lg font-semibold text-ink truncate">{{ $character->name }}</h3>

    @if ($character->role_label)
        <p class="mt-1 text-xs uppercase tracking-wider font-semibold text-ink-dim">{{ $character->role_label }}</p>
    @endif

    @if ($character->description)
        <p class="mt-3 text-sm text-ink-dim line-clamp-3">{{ $character->description }}</p>
    @endif

    <div class="mt-5 pt-4 border-t border-line flex items-center justify-between text-xs">
        @if ($category)
            <span class="rounded-full px-2.5 py-1 text-ink-mute uppercase tracking-wide font-semibold">
                {{ $category->name }}
            </span>
        @else
            <span></span>
        @endif

        <span class="inline-flex items-center gap-1.5 text-ink-mute">
            <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
            Gotowa
        </span>
    </div>
</a>
