@extends('layouts.app', ['title' => 'Nowe hasło — postac.ai'])

@section('content')
    <x-auth-card
        heading="Ustaw nowe hasło"
        subheading="Wpisz email i wybierz nowe hasło."
    >
        <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}" />

            <x-form-input
                name="email"
                label="Email"
                type="email"
                :value="$email"
                autocomplete="email"
                required
                autofocus
            />

            <x-form-input
                name="password"
                label="Nowe hasło"
                type="password"
                autocomplete="new-password"
                required
                hint="Min. 8 znaków."
            />

            <x-form-input
                name="password_confirmation"
                label="Powtórz hasło"
                type="password"
                autocomplete="new-password"
                required
            />

            <button type="submit" class="btn-glow mt-2 w-full">Zapisz hasło</button>
        </form>
    </x-auth-card>
@endsection
