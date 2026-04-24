@foreach ($characters as $character)
    <x-character-card :character="$character" />
@endforeach

@if ($characters->hasMorePages())
    <div
        hx-get="{{ $characters->nextPageUrl() }}"
        hx-trigger="revealed"
        hx-swap="outerHTML"
        hx-target="this"
        class="col-span-full flex justify-center py-6 text-sm text-base-content/60"
    >
        <span class="loading loading-dots loading-md"></span>
    </div>
@endif
