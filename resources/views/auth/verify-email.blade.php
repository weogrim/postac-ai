@extends('layouts.app', ['title' => 'Potwierdź email — postac.ai'])

@section('content')
    <x-auth-card
        heading="Potwierdź swój email"
        subheading="Wysłaliśmy link weryfikacyjny na Twój adres. Kliknij w niego, żeby aktywować konto."
    >
        @if (session('status') === 'verification-link-sent')
            <x-alert type="success" style="soft">Wysłaliśmy nowy link na Twój email.</x-alert>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary w-full">Wyślij ponownie link</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" hx-boost="false">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm w-full">Wyloguj się</button>
            </form>
        </div>
    </x-auth-card>
@endsection
