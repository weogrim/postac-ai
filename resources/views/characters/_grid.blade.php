@if ($characters->isEmpty())
    <p class="col-span-full py-12 text-center text-ink-mute">Brak postaci spełniających kryteria.</p>
@else
    @foreach ($characters as $character)
        <x-character-card :character="$character" />
    @endforeach
@endif
