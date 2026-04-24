@props(['character'])

<article class="group relative aspect-[3/4] overflow-hidden rounded-2xl bg-base-100 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-2 hover:ring-primary/60">
    @auth
        <form method="POST" action="{{ route('chat.store') }}" class="absolute inset-0 z-10">
            @csrf
            <input type="hidden" name="character_id" value="{{ $character->id }}">
            <button type="submit" class="absolute inset-0 w-full cursor-pointer" aria-label="Rozmawiaj z {{ $character->name }}"></button>
        </form>
    @else
        <a href="{{ route('login') }}" class="absolute inset-0 z-10" aria-label="Zaloguj, aby rozmawiać z {{ $character->name }}"></a>
    @endauth

    <img
        src="{{ $character->avatarUrl('square') }}"
        alt=""
        loading="lazy"
        class="pointer-events-none h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
    >

    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>

    <div class="pointer-events-none absolute inset-x-0 bottom-0 p-4 text-white">
        <h3 class="truncate text-lg font-semibold drop-shadow-sm">{{ $character->name }}</h3>
        <p class="truncate text-xs text-white/80">@ {{ Str::limit($character->author?->name ?? 'nieznany', 22) }}</p>
    </div>
</article>
