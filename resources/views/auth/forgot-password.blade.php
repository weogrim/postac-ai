@extends('layouts.app', ['title' => 'Zapomniałem hasła — postac.ai'])

@section('content')
    <x-auth-card
        heading="Zapomniałeś hasła?"
        subheading="Wpisz email, a wyślemy Ci link do resetu."
    >
        @if (session('status'))
            <x-alert type="success" style="soft">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
            @csrf

            <x-form-input
                name="email"
                label="Email"
                type="email"
                autocomplete="email"
                required
                autofocus
            />

            <button type="submit" class="btn-glow mt-2 w-full">Wyślij link</button>
        </form>

        <x-slot name="footer">
            Pamiętasz hasło?
            <a href="{{ route('login') }}" class="text-magenta hover:underline">Wróć do logowania</a>
        </x-slot>
    </x-auth-card>
@endsection
