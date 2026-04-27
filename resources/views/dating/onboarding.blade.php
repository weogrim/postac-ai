@extends('layouts.app', ['title' => 'Randki — zanim zaczniemy'])

@section('content')
    <div class="mx-auto w-full max-w-xl px-4 py-12">
        <div class="rounded-2xl bg-base-100 p-6 shadow-xl sm:p-8">
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">💕 Zanim zaczniesz</h1>
            <p class="mt-2 text-sm text-base-content/70">Sekcja Randki to zabawa z postaciami AI. Przeczytaj zanim wejdziesz.</p>

            <ul class="mt-6 space-y-3 text-sm">
                <li class="flex gap-3">
                    <span aria-hidden="true">🤖</span>
                    <span>To są postacie AI, nie prawdziwi ludzie — nawet jeśli rozmowa wciąga.</span>
                </li>
                <li class="flex gap-3">
                    <span aria-hidden="true">🎭</span>
                    <span>Rozmowy są rozrywkowe, nie terapeutyczne. Jeśli przechodzisz trudny moment, porozmawiaj z kimś bliskim albo zadzwoń <strong>116 111</strong>.</span>
                </li>
                <li class="flex gap-3">
                    <span aria-hidden="true">🚫</span>
                    <span>Zero NSFW — luźny flirt, nie sexting. Próby pójścia w sex będą odbijane.</span>
                </li>
                <li class="flex gap-3">
                    <span aria-hidden="true">📜</span>
                    <span>Pełen <a href="{{ route('legal.show', 'dating-terms') }}" target="_blank" class="link link-primary">regulamin sekcji Randki</a> obowiązuje.</span>
                </li>
            </ul>

            <form method="POST" action="{{ route('dating.onboarding') }}" class="mt-8 space-y-4">
                @csrf

                <label class="flex cursor-pointer gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200">
                    <input type="checkbox" name="accepted_dating_terms" value="1" class="checkbox checkbox-primary mt-0.5" required>
                    <span class="text-sm">Rozumiem, to zabawa z AI. Akceptuję regulamin sekcji Randki.</span>
                </label>

                @error('accepted_dating_terms')
                    <p class="text-xs text-error">{{ $message }}</p>
                @enderror

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('home') }}" class="btn btn-ghost">Wróć</a>
                    <button type="submit" class="btn btn-primary">Wchodzę</button>
                </div>
            </form>
        </div>
    </div>
@endsection
