@extends('layouts.app', ['title' => 'Dokończ rejestrację — postac.ai'])

@section('content')
    <x-auth-card
        heading="Dokończ rejestrację"
        subheading="Potrzebujemy daty urodzenia i zgód, żebyśmy mogli ruszyć."
    >
        <form method="POST" action="{{ route('auth.complete') }}" class="flex flex-col gap-4">
            @csrf

            <x-birthdate-picker
                name="birthdate"
                label="Data urodzenia"
                required
                hint="Musisz mieć ukończone 13 lat."
            />

            <fieldset class="fieldset gap-2">
                <label class="flex cursor-pointer items-start gap-2">
                    <input
                        type="checkbox"
                        name="accepted_terms"
                        value="1"
                        class="checkbox checkbox-sm mt-0.5"
                        @checked(old('accepted_terms'))
                    />
                    <span class="text-sm">
                        Akceptuję
                        <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="text-magenta hover:underline">regulamin</a>.
                    </span>
                </label>
                @error('accepted_terms')
                    <p class="fieldset-label text-error">{{ $message }}</p>
                @enderror

                <label class="flex cursor-pointer items-start gap-2">
                    <input
                        type="checkbox"
                        name="accepted_privacy"
                        value="1"
                        class="checkbox checkbox-sm mt-0.5"
                        @checked(old('accepted_privacy'))
                    />
                    <span class="text-sm">
                        Akceptuję
                        <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="text-magenta hover:underline">politykę prywatności</a>.
                    </span>
                </label>
                @error('accepted_privacy')
                    <p class="fieldset-label text-error">{{ $message }}</p>
                @enderror

                <label class="flex cursor-pointer items-start gap-2">
                    <input
                        type="checkbox"
                        name="accepted_parental"
                        value="1"
                        class="checkbox checkbox-sm mt-0.5"
                        @checked(old('accepted_parental'))
                    />
                    <span class="text-sm">
                        Posiadam zgodę rodzica lub opiekuna (wymagane dla osób w wieku 13–15 lat).
                    </span>
                </label>
                @error('accepted_parental')
                    <p class="fieldset-label text-error">{{ $message }}</p>
                @enderror
            </fieldset>

            <button type="submit" class="btn-glow mt-2 w-full">Zapisz i kontynuuj</button>
        </form>
    </x-auth-card>
@endsection
