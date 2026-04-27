@props(['character'])

<a
    href="{{ route('character.show', $character) }}"
    class="group relative block aspect-[3/4] overflow-hidden rounded-2xl bg-base-100 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-2 hover:ring-primary/60"
>
    <img
        src="{{ $character->avatarUrl('square') }}"
        alt=""
        loading="lazy"
        class="pointer-events-none h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
    >

    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>

    @if ($character->is_official)
        <span class="absolute right-2 top-2 rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold uppercase text-primary-content shadow">
            Oficjalna
        </span>
    @endif

    <div class="pointer-events-none absolute inset-x-0 bottom-0 p-4 text-white">
        <h3 class="truncate text-lg font-semibold drop-shadow-sm">{{ $character->name }}</h3>
        @if ($character->description)
            <p class="line-clamp-2 text-xs text-white/80">{{ $character->description }}</p>
        @elseif (! $character->is_official && $character->author)
            <p class="truncate text-xs text-white/70">@ {{ Str::limit($character->author->name, 22) }}</p>
        @endif
        @if ($character->popularity_24h > 0)
            <p class="mt-1 text-[10px] text-white/60">{{ $character->popularity_24h }} rozmów dziś</p>
        @endif
    </div>
</a>
